<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\GeneratedDocument;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * GeneratedDocumentService
 * 
 * Handles generation of documents from templates
 * - Fills templates with user-provided data
 * - Generates PDF/Word files
 * - Stores generated documents
 */
class GeneratedDocumentService
{
    private OpenAIService $openai;

    public function __construct(OpenAIService $openai)
    {
        $this->openai = $openai;
    }

    /**
     * Detect if user is requesting document generation
     * Returns the document type if detected, null otherwise
     * 
     * @param string $message User message
     * @return string|null Document type (e.g., "Contrat de travail") or null
     */
    public function detectDocumentGenerationRequest(string $message): ?string
    {
        $text = mb_strtolower($message);

        // Patterns indicating document generation request
        $generationPatterns = [
            'rédige' => true,
            'génère' => true,
            'crée' => true,
            'rédiger' => true,
            'générer' => true,
            'créer' => true,
            'monte' => true,
            'monter' => true,
            'prépare' => true,
            'préparer' => true,
            'fais un' => true,
            'fais une' => true,
            'fait un' => true,
            'fait une' => true,
            'fais moi' => true,
            'donne moi le fichier' => true,
            'téléchargeable' => true,
            'make me' => true,
            'create' => true,
            'write' => true,
            'draft' => true,
        ];

        // Check if any generation keyword is present
        $hasGenerationKeyword = false;
        foreach (array_keys($generationPatterns) as $pattern) {
            if (str_contains($text, $pattern)) {
                $hasGenerationKeyword = true;
                break;
            }
        }

        if (!$hasGenerationKeyword) {
            return null;
        }

        // Document types to search for
        $documentTypes = [
            'contrat de travail',
            'contrat de location',
            'contrat de bail',
            'contrat de services',
            'contrat commercial',
            'accord de confidentialité',
            'lettre de motivation',
            'cv',
            'facture',
            'devis',
            'lettre officielle',
            'procuration',
            'testament',
            'contrat d\'embauche',
            'contrat de vente',
        ];

        // Find which document type is requested
        foreach ($documentTypes as $docType) {
            if (str_contains($text, $docType)) {
                return $docType;
            }
        }

        return null;
    }

    /**
     * Find appropriate template for document type
     * 
     * @param string $documentType Document type requested
     * @param string $country User's country
     * @return DocumentTemplate|null
     */
    public function findTemplate(string $documentType, string $country): ?DocumentTemplate
    {
        // Search for template matching document type
        $template = DocumentTemplate::where('is_mobile_visible', true)
            ->where(function($q) use ($documentType) {
                $q->where('name', 'LIKE', "%{$documentType}%")
                  ->orWhere('description', 'LIKE', "%{$documentType}%");
            })
            ->where(function($q) use ($country) {
                $q->where('country', $country)
                  ->orWhereNull('country'); // General templates
            })
            ->first();

        if ($template) {
            Log::info('Document template found', [
                'type' => $documentType,
                'template_id' => $template->id,
                'template_name' => $template->name,
            ]);
            return $template;
        }

        Log::warning('No template found for document type', [
            'type' => $documentType,
            'country' => $country,
        ]);
        return null;
    }

    /**
     * Extract variables from user message
     * E.g., "nom: Jean Dupont, poste: Ingénieur"
     * 
     * @param string $message User message
     * @param array $templateVariables Expected variables from template
     * @return array Extracted variables
     */
    public function extractVariablesFromMessage(string $message, array $templateVariables = []): array
    {
        $extracted = [];

        // If template has specific variables, try to extract them
        if (!empty($templateVariables)) {
            foreach ($templateVariables as $var) {
                // Look for patterns like "nom: Jean" or "nom = Jean"
                if (preg_match("/\b{$var}\s*[:=]\s*([^\n,]+)/i", $message, $matches)) {
                    $extracted[$var] = trim($matches[1], ' \t\n\r\0\x0B,');
                }
            }
        }

        // Also try to extract common patterns
        $commonFields = [
            'nom' => 'name',
            'prenom' => 'firstname',
            'téléphone' => 'phone',
            'email' => 'email',
            'adresse' => 'address',
            'ville' => 'city',
            'pays' => 'country',
            'date' => 'date',
            'entreprise' => 'company',
            'poste' => 'position',
            'salaire' => 'salary',
            'durée' => 'duration',
            'date de début' => 'start_date',
            'date de fin' => 'end_date',
        ];

        foreach ($commonFields as $frenchField => $englishKey) {
            if (empty($extracted[$englishKey])) {
                if (preg_match("/\b{$frenchField}\s*[:=]\s*([^\n,]+)/i", $message, $matches)) {
                    $extracted[$englishKey] = trim($matches[1], ' \t\n\r\0\x0B,');
                }
            }
        }

        Log::info('Variables extracted from message', [
            'extracted_count' => count($extracted),
            'variables' => array_keys($extracted),
        ]);

        return $extracted;
    }

    /**
     * Generate filled document using OpenAI
     * 
     * @param DocumentTemplate $template
     * @param array $variables User-provided variables
     * @param string $country User's country
     * @param string $aiModel AI model to use
     * @return array|null Generated document content or null on failure
     */
    public function generateFilledDocument(
        DocumentTemplate $template,
        array $variables,
        string $country,
        string $aiModel = 'gpt-4o-mini'
    ): ?array {
        try {
            // Build prompt to fill template
            $prompt = $this->buildFillingPrompt($template, $variables, $country);

            Log::info('Document generation: Calling OpenAI to fill template', [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'variables_count' => count($variables),
                'model' => $aiModel,
            ]);

            // Call OpenAI to generate filled document
            $response = $this->openai->chat(
                $prompt,
                [], // No conversation history needed for document generation
                $aiModel
            );

            if (!$response['success']) {
                Log::error('Document generation: OpenAI failed', [
                    'error' => $response['error'] ?? 'unknown',
                ]);
                return null;
            }

            $filledContent = $response['message'] ?? '';

            Log::info('Document generation: Content generated successfully', [
                'content_length' => strlen($filledContent),
                'tokens_used' => $response['usage']['total_tokens'] ?? 0,
            ]);

            return [
                'content' => $filledContent,
                'usage' => $response['usage'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Document generation failed', [
                'error' => $e->getMessage(),
                'template_id' => $template->id,
            ]);
            return null;
        }
    }

    /**
     * Build prompt for OpenAI to fill document template
     * 
     * @param DocumentTemplate $template
     * @param array $variables
     * @param string $country
     * @return string
     */
    private function buildFillingPrompt(DocumentTemplate $template, array $variables, string $country): string
    {
        $variablesText = "Variables à utiliser:\n";
        foreach ($variables as $key => $value) {
            $variablesText .= "- $key: $value\n";
        }

        // Prepare template content outside of HEREDOC to avoid ?? syntax error
        $templateContent = $template->ai_context ?? $template->description;

        $prompt = <<<PROMPT
Tu es un expert juridique spécialisé dans la rédaction de documents légaux pour {$country}.

Je vais te donner un modèle de document et des données à utiliser.
Tu dois remplir le modèle de document complètement avec les données fournies, en respectant les lois et la culture juridique de {$country}.

**MODÈLE DE DOCUMENT:**
{$template->description}

{$variablesText}

**INSTRUCTIONS:**
1. Remplissez tous les espaces vides du modèle avec les données fournies
2. Utilisez un langage juridique professionnel et formel en français
3. Assurez-vous que le document est conforme à la loi de {$country}
4. Si une variable n'est pas fournie, laissez un espace [À REMPLIR PAR L'UTILISATEUR]
5. Retournez le document rempli UNIQUEMENT, sans explications supplémentaires
6. Formatez le document de manière lisible avec des sections claires

Voici le contenu du modèle à remplir:
{$templateContent}

Remplissez ce modèle maintenant avec les variables fournies ci-dessus.
PROMPT;

        return $prompt;
    }

    /**
     * Build formatted HTML from document content
     * Inspired by admin invoice generation (billpay.blade.php pattern)
     * 
     * @param string $content Document content
     * @param string $fileName Template name
     * @param array $variables Extracted variables
     * @return string HTML string
     */
    private function buildDocumentHTML(string $content, string $fileName, array $variables = []): string
    {
        $date = now()->format('d M Y');
        $time = now()->format('H:i');
        
        // Build variables display
        $variablesHTML = '';
        if (!empty($variables)) {
            $variablesHTML = '<div class="document-variables">';
            foreach ($variables as $key => $value) {
                $variablesHTML .= sprintf(
                    '<div class="variable-row"><strong>%s:</strong> %s</div>',
                    htmlspecialchars(ucfirst(str_replace('_', ' ', $key))),
                    htmlspecialchars($value)
                );
            }
            $variablesHTML .= '</div>';
        }

        // Format content with proper line breaks and indentation
        $formattedContent = $this->formatContentHTML($content);

        $html = $this->getDocumentHTMLTemplate();

        return str_replace(
            ['%s_name%', '%s_date%', '%s_time%', '%s_variables%', '%s_content%'],
            [
                htmlspecialchars($fileName),
                $date,
                $time,
                $variablesHTML,
                $formattedContent
            ],
            $html
        );
    }

    /**
     * Build document HTML template with placeholders
     * @return string HTML template with %s_name%, %s_date%, %s_time%, %s_variables%, %s_content% placeholders
     */
    private function getDocumentHTMLTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>%s_name%</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f0f0f0;
            padding: 20px;
        }
        
        .document-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .document-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
        }
        
        .document-header h1 {
            font-size: 28px;
            margin-bottom: 20px;
        }
        
        .document-meta {
            display: flex;
            gap: 40px;
            margin-top: 20px;
        }
        
        .document-meta-item {
            font-size: 14px;
        }
        
        .document-meta-label {
            font-weight: 600;
            opacity: 0.9;
            display: block;
            margin-bottom: 4px;
        }
        
        .document-meta-value {
            display: block;
        }
        
        .document-content {
            padding: 40px;
            line-height: 1.8;
        }
        
        .document-content p {
            margin-bottom: 15px;
        }
        
        .document-content h2 {
            margin-top: 25px;
            margin-bottom: 15px;
            color: #667eea;
            font-size: 18px;
            padding-bottom: 8px;
            border-bottom: 2px solid #667eea;
        }
        
        .document-content h3 {
            margin-top: 15px;
            margin-bottom: 10px;
            color: #764ba2;
            font-size: 14px;
            font-weight: 600;
        }
        
        .document-footer {
            background: #f5f5f5;
            padding: 20px 40px;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #999;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .document-footer-text {
            display: flex;
            gap: 30px;
        }
        
        .footer-item {
            display: flex;
            flex-direction: column;
        }
        
        .footer-label {
            font-weight: 600;
            color: #667eea;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .footer-value {
            margin-top: 3px;
            color: #333;
            font-size: 12px;
        }
        
        @media print {
            body {
                background: white;
            }
            
            .document-container {
                box-shadow: none;
                margin: 0;
                max-width: 100%;
            }
        }
        
        .document-content ul, .document-content ol {
            margin-left: 20px;
            margin-bottom: 12px;
        }
        
        .document-content li {
            margin-bottom: 6px;
        }
    </style>
</head>
<body>
    <div class="document-container">
        <div class="document-header">
            <h1>%s_name%</h1>
            <div class="document-meta">
                <div class="document-meta-item">
                    <span class="document-meta-label">Date de génération</span>
                    <span class="document-meta-value">%s_date% à %s_time%</span>
                </div>
                <div class="document-meta-item">
                    <span class="document-meta-label">Type de document</span>
                    <span class="document-meta-value">Document généré par IA</span>
                </div>
            </div>
        </div>
        
        %s_variables%
        
        <div class="document-content">
            %s_content%
        </div>
        
        <div class="document-footer">
            <div class="document-footer-text">
                <div class="footer-item">
                    <span class="footer-label">Statut</span>
                    <span class="footer-value">Document généré</span>
                </div>
                <div class="footer-item">
                    <span class="footer-label">Format</span>
                    <span class="footer-value">PDF - Téléchargeable</span>
                </div>
            </div>
            <div class="footer-item">
                <span class="footer-label">Généré par</span>
                <span class="footer-value">Dossy AI</span>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Format text content as HTML with proper paragraphs
     * 
     * @param string $content Plain text content
     * @return string HTML formatted content
     */
    private function formatContentHTML(string $content): string
    {
        // Convert plain text to HTML paragraphs
        $lines = explode("\n", trim($content));
        $html = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // Detect section headers (ALL CAPS or numbered sections)
            if (preg_match('/^([A-Z\d\s\-]{5,}|(?:\d+\.?\s+)?[A-Z][A-Z\s]+):?$/', $line)) {
                $html .= '<h2>' . htmlspecialchars($line) . '</h2>';
            }
            // Detect subsections
            elseif (preg_match('/^(?:\s{2,4})?([A-Z][a-z\s]+):\s*/', $line)) {
                $html .= '<h3>' . htmlspecialchars($line) . '</h3>';
            }
            // Regular paragraph
            else {
                $html .= '<p>' . htmlspecialchars($line) . '</p>';
            }
        }
        
        return $html;
    }

    /**
     * Save generated document to storage
     * 
     * @param string $content Document content
     * @param string $fileName File name / Template name
     * @param array $variables Extracted variables for HTML display
     * @param string $fileType File type (pdf, docx, txt)
     * @return string|null File path or null on failure
     */
    public function saveDocumentToStorage(
        string $content,
        string $fileName,
        array $variables = [],
        string $fileType = 'html'
    ): ?string {
        try {
            // Create directory if not exists
            $directory = 'generated_documents/' . date('Y/m');

            // Build HTML from content
            $htmlContent = $this->buildDocumentHTML($content, $fileName, $variables);

            // Generate unique file name with .html extension
            $uniqueName = time() . '_' . uniqid() . '.html';
            $filePath = $directory . '/' . $uniqueName;

            // Save HTML to storage (Flutter will convert to PDF)
            Storage::disk('public')->put($filePath, $htmlContent);

            Log::info('Document HTML saved to storage', [
                'file_path' => $filePath,
                'file_name' => $fileName,
                'html_size' => strlen($htmlContent),
                'content_size' => strlen($content),
            ]);

            return $filePath;
        } catch (\Exception $e) {
            Log::error('Failed to save document to storage', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create GeneratedDocument record
     * 
     * @param User $user
     * @param Conversation $conversation
     * @param int|null $messageId
     * @param DocumentTemplate $template
     * @param string $content Generated content
     * @param string $filePath File path in storage
     * @param array $extractedVariables
     * @param array $generatedBy AI generation metadata
     * @return GeneratedDocument|null
     */
    public function createGeneratedDocument(
        User $user,
        Conversation $conversation,
        ?int $messageId,
        DocumentTemplate $template,
        string $content,
        string $filePath,
        array $extractedVariables,
        array $generatedBy
    ): ?GeneratedDocument {
        try {
            $document = GeneratedDocument::create([
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
                'message_id' => $messageId,
                'template_id' => $template->id,
                'template_name' => $template->name,
                'document_content' => substr($content, 0, 5000), // Store first 5000 chars as preview
                'file_path' => $filePath,
                'file_name' => $template->name . '_' . date('Y-m-d_His') . '.pdf',
                'file_type' => 'html', // Stored as HTML, Flutter will convert to PDF
                'file_size' => Storage::disk('public')->exists($filePath)
                    ? Storage::disk('public')->size($filePath)
                    : strlen($content),
                'extracted_variables' => $extractedVariables,
                'status' => 'generated',
                'generated_by' => $generatedBy,
            ]);

            Log::info('Generated document record created', [
                'document_id' => $document->id,
                'user_id' => $user->id,
                'template_id' => $template->id,
            ]);

            return $document;
        } catch (\Exception $e) {
            Log::error('Failed to create generated document record', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get full document for download
     * 
     * @param GeneratedDocument $document
     * @return string|null
     */
    public function getDocumentContent(GeneratedDocument $document): ?string
    {
        try {
            if (Storage::disk('public')->exists($document->file_path)) {
                return Storage::disk('public')->get($document->file_path);
            }

            Log::warning('Generated document file not found', [
                'document_id' => $document->id,
                'file_path' => $document->file_path,
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve document content', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Complete workflow: detect → find template → extract → generate → save
     * 
     * @param string $message User message
     * @param string $country User's country
     * @param User $user
     * @param Conversation $conversation
     * @param string $aiModel AI model to use
     * @return array|null ['document' => GeneratedDocument, 'instruction' => string] or null
     */
    public function processDocumentGenerationRequest(
        string $message,
        string $country,
        User $user,
        Conversation $conversation,
        string $aiModel = 'gpt-4o-mini'
    ): ?array {
        // 1. Detect if this is a document generation request
        $documentType = $this->detectDocumentGenerationRequest($message);
        if (empty($documentType)) {
            return null;
        }

        Log::info('Document generation request detected', [
            'document_type' => $documentType,
            'user_id' => $user->id,
            'country' => $country,
        ]);

        // 2. Find appropriate template
        $template = $this->findTemplate($documentType, $country);
        if (!$template) {
            Log::warning('No template found for generation request', [
                'type' => $documentType,
                'country' => $country,
            ]);

            return [
                'document' => null,
                'instruction' => "Désolé, je n'ai pas de modèle pour '{$documentType}' au {$country}. Veuillez essayer un autre type de document.",
            ];
        }

        // 3. Extract variables from message
        $variables = $this->extractVariablesFromMessage($message, $template->variables ?? []);

        // If no variables extracted, ask user to provide them
        if (empty($variables) && !empty($template->variables)) {
            $requiredVars = implode(', ', $template->variables);
            return [
                'document' => null,
                'instruction' => "J'ai trouvé le modèle '{$template->name}'. Pour remplir le document, veuillez fournir les informations suivantes: {$requiredVars}",
            ];
        }

        // 4. Generate filled document
        $generated = $this->generateFilledDocument($template, $variables, $country, $aiModel);
        if (!$generated) {
            return [
                'document' => null,
                'instruction' => "Erreur lors de la génération du document. Veuillez réessayer.",
            ];
        }

        // 5. Save to storage (saves as HTML with variables visible)
        $filePath = $this->saveDocumentToStorage(
            $generated['content'],
            $template->name,
            $variables,
            'html'
        );

        if (!$filePath) {
            return [
                'document' => null,
                'instruction' => "Erreur lors de la sauvegarde du document. Veuillez réessayer.",
            ];
        }

        // 6. Create document record
        $document = $this->createGeneratedDocument(
            $user,
            $conversation,
            null,
            $template,
            $generated['content'],
            $filePath,
            $variables,
            [
                'model' => $aiModel,
                'tokens' => $generated['usage']['total_tokens'] ?? 0,
            ]
        );

        if (!$document) {
            return [
                'document' => null,
                'instruction' => "Erreur lors de l'enregistrement du document. Veuillez réessayer.",
            ];
        }

        Log::info('Document generation completed successfully', [
            'document_id' => $document->id,
            'template_id' => $template->id,
        ]);

        return [
            'document' => $document,
            'instruction' => "Document '{$template->name}' généré avec succès ! Vous pouvez le télécharger.",
        ];
    }
}
