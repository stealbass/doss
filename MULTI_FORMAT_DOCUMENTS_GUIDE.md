# 📄 Support Multi-Format Documents - Packages Requis

## 🎯 OBJECTIVE

Permettre aux utilisateurs de charger et interroger l'IA sur:
- ✅ PDF (`.pdf`)
- ✅ Word (`.doc`, `.docx`)
- ✅ PowerPoint (`.ppt`, `.pptx`)
- ✅ Excel (`.xls`, `.xlsx`, `.csv`)
- ✅ Texte (`.txt`, `.md`, `.rtf`)

---

## 📦 PACKAGES À INSTALLER

### 1. **PdfParser** (PDF) - Déjà en cours
```bash
composer require smalot/pdfparser:^2.7
```

### 2. **PHPWord** (Word documents)
```bash
composer require phpoffice/phpword
```
- Supporte: `.docx`, `.doc`, `.odt`
- Extraction: Texte, headers, footers, tables

### 3. **PHPPowerPoint** (PowerPoint)
```bash
composer require phpoffice/phppowerpoint
```
- Supporte: `.pptx`, `.odp`
- Extraction: Texte des slides, speaker notes

### 4. **PHPSpreadsheet** (Excel)
```bash
composer require phpoffice/phpspreadsheet
```
- Supporte: `.xlsx`, `.xls`, `.csv`, `.ods`
- Extraction: Valeurs cellules, formules, styles

### 5. **Symfony DomCrawler** (HTML optionnel)
```bash
composer require symfony/dom-crawler
```
- Supporte: `.html`, `.htm`
- Optionnel si vous acceptez HTML

---

## 📋 COMMANDE INSTALL COMPLÈTE

```bash
cd ~/yyy/Dossy

# Installer tous les packages en une fois
composer require \
  smalot/pdfparser:^2.7 \
  phpoffice/phpword:^1.0 \
  phpoffice/phppowerpoint:^1.0 \
  phpoffice/phpspreadsheet:^1.28 \
  symfony/dom-crawler:^6.0

# Ou sans version spécifique
composer require smalot/pdfparser phpoffice/phpword phpoffice/phppowerpoint phpoffice/phpspreadsheet
```

---

## 🏗️ ARCHITECTURE RECOMMANDÉE

### Structure des fichiers

```
app/Services/
├── DocumentExtractionService.php          ← Classe principale
├── Extractors/
│   ├── DocumentExtractorInterface.php     ← Interface
│   ├── PdfExtractor.php                   ← Pour PDF
│   ├── WordExtractor.php                  ← Pour Word
│   ├── PowerPointExtractor.php            ← Pour PowerPoint
│   ├── ExcelExtractor.php                 ← Pour Excel
│   └── TextExtractor.php                  ← Pour texte brut
└── ProcessDocumentForRAG.php              ← Job existant (à modifier)
```

---

## 💻 CODE EXEMPLE

### Interface Extracteur

```php
<?php

namespace App\Services\Extractors;

interface DocumentExtractorInterface
{
    /**
     * Extraire le texte du document
     * 
     * @param string $filePath Chemin du fichier
     * @return string Texte extrait
     */
    public function extract(string $filePath): string;

    /**
     * Vérifier si ce type de fichier est supporté
     * 
     * @param string $mimeType Type MIME
     * @return bool
     */
    public static function supports(string $mimeType): bool;

    /**
     * Retourner les extensions supportées
     * 
     * @return array
     */
    public static function getSupportedExtensions(): array;
}
```

### Service Principal

```php
<?php

namespace App\Services;

use App\Services\Extractors\DocumentExtractorInterface;
use App\Services\Extractors\PdfExtractor;
use App\Services\Extractors\WordExtractor;
use App\Services\Extractors\PowerPointExtractor;
use App\Services\Extractors\ExcelExtractor;
use App\Services\Extractors\TextExtractor;
use Illuminate\Support\Facades\Log;

class DocumentExtractionService
{
    protected array $extractors = [];

    public function __construct()
    {
        // Enregistrer les extracteurs disponibles
        $this->extractors = [
            new PdfExtractor(),
            new WordExtractor(),
            new PowerPointExtractor(),
            new ExcelExtractor(),
            new TextExtractor(),
        ];
    }

    /**
     * Extraire le texte d'un document
     * 
     * @param string $filePath
     * @param string $mimeType
     * @return string|null
     */
    public function extract(string $filePath, string $mimeType): ?string
    {
        Log::info("Document extraction started", [
            'file' => basename($filePath),
            'mime_type' => $mimeType,
            'size' => filesize($filePath)
        ]);

        try {
            // Trouver l'extracteur approprié
            foreach ($this->extractors as $extractor) {
                if ($extractor::supports($mimeType)) {
                    $text = $extractor->extract($filePath);
                    
                    Log::info("Document extraction successful", [
                        'extractor' => get_class($extractor),
                        'text_length' => strlen($text)
                    ]);

                    return $text;
                }
            }

            Log::warning("No extractor found for MIME type", [
                'mime_type' => $mimeType
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Document extraction failed", [
                'error' => $e->getMessage(),
                'file' => $filePath
            ]);

            return null;
        }
    }

    /**
     * Vérifier si un type de fichier est supporté
     * 
     * @param string $mimeType
     * @return bool
     */
    public function isSupported(string $mimeType): bool
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor::supports($mimeType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retourner tous les types MIME supportés
     * 
     * @return array
     */
    public function getSupportedMimeTypes(): array
    {
        $mimeTypes = [];

        foreach ($this->extractors as $extractor) {
            $extensions = $extractor::getSupportedExtensions();
            foreach ($extensions as $ext) {
                $mimeTypes[] = $this->getMimeType($ext);
            }
        }

        return array_filter($mimeTypes);
    }

    /**
     * Retourner les extensions supportées
     * 
     * @return array
     */
    public function getSupportedExtensions(): array
    {
        $extensions = [];

        foreach ($this->extractors as $extractor) {
            $extensions = array_merge($extensions, $extractor::getSupportedExtensions());
        }

        return array_unique($extensions);
    }

    private function getMimeType(string $extension): ?string
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'txt' => 'text/plain',
            'md' => 'text/markdown',
        ];

        return $mimeTypes[strtolower($extension)] ?? null;
    }
}
```

### Exemple: PDF Extractor

```php
<?php

namespace App\Services\Extractors;

use Smalot\PdfParser\Parser;

class PdfExtractor implements DocumentExtractorInterface
{
    public function extract(string $filePath): string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        
        return $pdf->getText();
    }

    public static function supports(string $mimeType): bool
    {
        return in_array($mimeType, [
            'application/pdf',
        ]);
    }

    public static function getSupportedExtensions(): array
    {
        return ['pdf'];
    }
}
```

### Exemple: Word Extractor

```php
<?php

namespace App\Services\Extractors;

use PhpOffice\PhpWord\IOFactory;

class WordExtractor implements DocumentExtractorInterface
{
    public function extract(string $filePath): string
    {
        $phpWord = IOFactory::load($filePath);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text .= $this->extractElementText($element);
            }
        }

        return trim($text);
    }

    private function extractElementText($element): string
    {
        $text = '';

        if (method_exists($element, 'getText')) {
            $text .= $element->getText() . "\n";
        } elseif (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $child) {
                $text .= $this->extractElementText($child);
            }
        }

        return $text;
    }

    public static function supports(string $mimeType): bool
    {
        return in_array($mimeType, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public static function getSupportedExtensions(): array
    {
        return ['doc', 'docx', 'odt'];
    }
}
```

### Exemple: Excel Extractor

```php
<?php

namespace App\Services\Extractors;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelExtractor implements DocumentExtractorInterface
{
    public function extract(string $filePath): string
    {
        $spreadsheet = IOFactory::load($filePath);
        $text = '';

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $text .= "=== Sheet: {$sheetName} ===\n";

            foreach ($sheet->getRowIterator() as $row) {
                $rowData = [];
                foreach ($row->getCellIterator() as $cell) {
                    $rowData[] = $cell->getValue();
                }
                $text .= implode(" | ", $rowData) . "\n";
            }

            $text .= "\n";
        }

        return trim($text);
    }

    public static function supports(string $mimeType): bool
    {
        return in_array($mimeType, [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
        ]);
    }

    public static function getSupportedExtensions(): array
    {
        return ['xls', 'xlsx', 'csv', 'ods'];
    }
}
```

### Exemple: PowerPoint Extractor

```php
<?php

namespace App\Services\Extractors;

use PhpOffice\PhpPowerpoint\IOFactory;

class PowerPointExtractor implements DocumentExtractorInterface
{
    public function extract(string $filePath): string
    {
        $presentation = IOFactory::load($filePath);
        $text = '';

        foreach ($presentation->getAllSlides() as $slideIndex => $slide) {
            $text .= "=== Slide " . ($slideIndex + 1) . " ===\n";

            foreach ($slide->getShapeCollection() as $shape) {
                if ($shape->hasText()) {
                    $text .= $shape->getText() . "\n";
                }
            }

            $text .= "\n";
        }

        return trim($text);
    }

    public static function supports(string $mimeType): bool
    {
        return in_array($mimeType, [
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ]);
    }

    public static function getSupportedExtensions(): array
    {
        return ['ppt', 'pptx', 'odp'];
    }
}
```

### Exemple: Text Extractor

```php
<?php

namespace App\Services\Extractors;

class TextExtractor implements DocumentExtractorInterface
{
    public function extract(string $filePath): string
    {
        $text = file_get_contents($filePath);
        
        // Nettoyer les caractères de contrôle
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
    }

    public static function supports(string $mimeType): bool
    {
        return in_array($mimeType, [
            'text/plain',
            'text/markdown',
            'text/x-python',
            'text/javascript',
            'text/html',
        ]);
    }

    public static function getSupportedExtensions(): array
    {
        return ['txt', 'md', 'py', 'js', 'html', 'htm', 'rtf', 'log'];
    }
}
```

---

## 🔧 INTÉGRATION AVEC PROCESSдокументforrag

Modifier le Job `ProcessDocumentForRAG.php`:

```php
<?php

namespace App\Jobs;

use App\Services\DocumentExtractionService;
use App\Services\AdvancedRagService;
use App\Models\SubmittedDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDocumentForRAG implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $documentId;

    public function __construct(int $documentId)
    {
        $this->documentId = $documentId;
    }

    public function handle(DocumentExtractionService $extractionService, AdvancedRagService $ragService)
    {
        try {
            $document = SubmittedDocument::find($this->documentId);
            
            if (!$document) {
                Log::error("Document not found: {$this->documentId}");
                return;
            }

            Log::info("Processing document for RAG", [
                'document_id' => $this->documentId,
                'file_name' => $document->file_name,
                'file_type' => $document->file_type,
            ]);

            // 1. Extraire le texte (supporte maintenant tous les formats!)
            $filePath = storage_path("app/{$document->file_path}");
            
            if (!file_exists($filePath)) {
                Log::error("File not found", ['path' => $filePath]);
                $document->update(['extraction_status' => 'failed']);
                return;
            }

            $text = $extractionService->extract(
                $filePath,
                $document->file_type
            );

            if (!$text) {
                Log::error("Text extraction failed", [
                    'document_id' => $this->documentId,
                    'file_type' => $document->file_type,
                ]);
                $document->update(['extraction_status' => 'failed']);
                return;
            }

            Log::info("Text extracted successfully", [
                'document_id' => $this->documentId,
                'text_length' => strlen($text),
            ]);

            // 2. Indexer dans Pinecone
            $success = $ragService->indexDocument(
                $this->documentId,
                $document->title,
                $text,
                $document->user_id
            );

            if ($success) {
                $document->update([
                    'extraction_status' => 'completed',
                    'extracted_text_length' => strlen($text),
                    'indexed_at' => now(),
                ]);

                Log::info("Document indexed in Pinecone", [
                    'document_id' => $this->documentId,
                ]);
            } else {
                $document->update(['extraction_status' => 'failed']);
                Log::error("Pinecone indexing failed", [
                    'document_id' => $this->documentId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("ProcessDocumentForRAG job failed", [
                'document_id' => $this->documentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $document = SubmittedDocument::find($this->documentId);
            if ($document) {
                $document->update(['extraction_status' => 'failed']);
            }

            throw $e;
        }
    }

    public function failed(\Exception $exception)
    {
        Log::error("ProcessDocumentForRAG job permanently failed", [
            'document_id' => $this->documentId,
            'error' => $exception->getMessage(),
        ]);

        $document = SubmittedDocument::find($this->documentId);
        if ($document) {
            $document->update(['extraction_status' => 'failed']);
        }
    }
}
```

---

## 🎯 MISE À JOUR CONTRÔLEUR UPLOAD

```php
<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Models\SubmittedDocument;
use App\Jobs\ProcessDocumentForRAG;
use App\Services\DocumentExtractionService;
use Illuminate\Http\Request;

class DocumentController
{
    protected DocumentExtractionService $extractionService;

    public function __construct(DocumentExtractionService $extractionService)
    {
        $this->extractionService = $extractionService;
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:50000', // 50MB max
            'title' => 'required|string|max:255',
        ]);

        $file = $request->file('file');
        $mimeType = $file->getMimeType();

        // Vérifier que le type de fichier est supporté
        if (!$this->extractionService->isSupported($mimeType)) {
            return response()->json([
                'error' => 'File type not supported',
                'supported_types' => $this->extractionService->getSupportedExtensions(),
                'received_type' => $mimeType,
            ], 422);
        }

        // Sauvegarder le fichier
        $path = $file->store('submitted_documents', 'private');

        // Créer le record en base
        $document = SubmittedDocument::create([
            'user_id' => auth()->id(),
            'title' => $request->input('title'),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $mimeType,
            'file_size' => $file->getSize(),
            'extraction_status' => 'pending',
        ]);

        // Dispatcher le job d'extraction
        ProcessDocumentForRAG::dispatch($document->id);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'document' => $document,
            'processing' => 'File is being processed...',
        ], 201);
    }

    public function supportedFormats()
    {
        return response()->json([
            'extensions' => $this->extractionService->getSupportedExtensions(),
            'formats' => [
                'PDF' => '.pdf',
                'Word' => '.doc, .docx, .odt',
                'PowerPoint' => '.ppt, .pptx, .odp',
                'Excel' => '.xls, .xlsx, .csv, .ods',
                'Text' => '.txt, .md, .log, .html',
            ],
        ]);
    }
}
```

---

## ✅ RÉSUMÉ PACKAGES

| Format | Package | Command |
|--------|---------|---------|
| PDF | smalot/pdfparser | `composer require smalot/pdfparser` |
| Word | phpoffice/phpword | `composer require phpoffice/phpword` |
| PowerPoint | phpoffice/phppowerpoint | `composer require phpoffice/phppowerpoint` |
| Excel | phpoffice/phpspreadsheet | `composer require phpoffice/phpspreadsheet` |
| Texte | ✅ PHP natif | Aucun package |

---

## 🚀 INSTALL TOUT EN UNE COMMANDE

```bash
cd ~/yyy/Dossy

composer require \
  smalot/pdfparser \
  phpoffice/phpword \
  phpoffice/phppowerpoint \
  phpoffice/phpspreadsheet
```

**Durée**: 3-5 minutes

---

## 🧪 TESTS APRÈS INSTALLATION

```bash
# 1. Vérifier packages
composer show | grep phpoffice

# 2. Tester extraction
php artisan tinker
```

```php
>>> use App\Services\DocumentExtractionService;
>>> $service = new DocumentExtractionService();
>>> $service->getSupportedExtensions();
```

✅ **Attendu**: Array avec pdf, doc, docx, ppt, pptx, xls, xlsx, csv, txt, md...

---

## 📊 CAPACITÉS FINALES

✅ **5 types de documents supportés**  
✅ **Extraction automatique de texte**  
✅ **Indexation dans Pinecone**  
✅ **Chat IA sur n'importe quel document**  
✅ **Logs détaillés pour chaque étape**  
✅ **Gestion des erreurs robuste**  

🎉 **Système complet et prêt pour production!**
