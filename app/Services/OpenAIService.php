<?php

namespace App\Services;

use App\Models\MobileAppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI Service
 * 
 * Handles interactions with OpenAI GPT models for chat completion
 */
class OpenAIService
{
    private string $apiKey;
    private string $defaultModel = 'gpt-4o-mini';
    private int $maxTokens = 2000;
    private float $temperature = 0.7;

    public function __construct()
    {
        // Prioritize MobileAppSetting over .env configuration
        $mobileSettings = MobileAppSetting::first();
        
        if ($mobileSettings && !empty($mobileSettings->openai_api_key)) {
            $this->apiKey = $mobileSettings->openai_api_key;
        } else {
            $this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY', ''));
        }
    }

    /**
     * Generate chat completion with context (RAG-enhanced)
     * 
     * @param string $userMessage User's message
     * @param string $context RAG context
     * @param array $conversationHistory Previous messages
     * @param string $model Model to use
     * @return array Response with message and token usage
     */
    public function chatWithContext(
        string $userMessage,
        string $context = '',
        array $conversationHistory = [],
        string $model = null
    ): array {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'OpenAI API key not configured',
            ];
        }

        $model = $this->resolveModel($model ?? $this->defaultModel);

        try {
            // Build messages array
            $messages = $this->buildMessages($userMessage, $context, $conversationHistory);

            Log::info('OpenAI: sending request', [
                'model' => $model,
                'messages_count' => count($messages),
                'api_key_set' => !empty($this->apiKey),
                'api_key_prefix' => substr($this->apiKey, 0, 10) . '...',
            ]);

            // Call OpenAI API
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => $this->maxTokens,
                    'temperature' => $this->temperature,
                ]);

            Log::info('OpenAI: response received', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'message' => $data['choices'][0]['message']['content'] ?? '',
                    'model' => $data['model'] ?? $model,
                    'tokens_used' => [
                        'prompt' => $data['usage']['prompt_tokens'] ?? 0,
                        'completion' => $data['usage']['completion_tokens'] ?? 0,
                        'total' => $data['usage']['total_tokens'] ?? 0,
                    ],
                    'finish_reason' => $data['choices'][0]['finish_reason'] ?? 'unknown',
                ];
            }

            // Handle API errors
            $error = $response->json();
            Log::error("OpenAI API error: " . json_encode($error));

            return [
                'success' => false,
                'error' => $error['error']['message'] ?? 'Unknown API error',
                'error_type' => $error['error']['type'] ?? 'unknown',
            ];
        } catch (\Exception $e) {
            Log::error("OpenAI service error: " . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to communicate with OpenAI: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Resolve requested model to a currently supported one.
     * Silently upgrades deprecated aliases to a modern default.
     */
    private function resolveModel(string $model): string
    {
        $aliases = [
            // Common deprecated/alias models -> stable default
            'gpt-3.5-turbo' => 'gpt-4o-mini',
            'gpt-4' => 'gpt-4o-mini',
            'gpt-4-turbo' => 'gpt-4o-mini',
            'gpt-4-turbo-preview' => 'gpt-4o-mini',
        ];

        return $aliases[$model] ?? $model;
    }

    /**
     * Simple chat completion without RAG context
     * 
     * @param string $message User message
     * @param array $conversationHistory Previous messages
     * @param string $model Model to use
     * @return array Response
     */
    public function chat(
        string $message,
        array $conversationHistory = [],
        string $model = null
    ): array {
        return $this->chatWithContext($message, '', $conversationHistory, $model);
    }

    /**
     * Build messages array for OpenAI API
     * 
     * @param string $userMessage Current user message
     * @param string $context RAG context
     * @param array $history Conversation history
     * @return array Messages array
     */
    private function buildMessages(string $userMessage, string $context, array $history): array
    {
        $messages = [];

        // System message with RAG context if available
        $systemMessage = $this->buildSystemMessage($context);
        $messages[] = [
            'role' => 'system',
            'content' => $systemMessage,
        ];

        // Add conversation history
        foreach ($history as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }
        }

        // Add current user message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        return $messages;
    }

    /**
     * Build system message with RAG context
     * 
     * @param string $context RAG context (includes country-specific legal instructions)
     * @return string System message
     */
    private function buildSystemMessage(string $context): string
    {
        // Le contexte contient déjà les instructions spécifiques au pays
        // ajoutées par ChatController::getCountryAIContext()
        
        $basePrompt = "Tu es Dossy IA, un assistant juridique intelligent spécialisé dans le droit africain. "
            . "Tu aides les avocats, juristes et étudiants en droit avec des réponses précises et fiables. "
            . "Tes réponses doivent être professionnelles, basées sur des faits juridiques, et citées lorsque possible.\n\n";

        if (!empty($context)) {
            // Le contexte inclut :
            // 1. Les règles strictes de juridiction (OHADA, Code Famille, etc.)
            // 2. Les documents RAG de la bibliothèque juridique
            // 3. Les modèles de documents
            // 4. Les ressources fiscales et sociales
            $basePrompt .= $context;
            
            $basePrompt .= "\n\n=== RÈGLES STRICTES D'UTILISATION ===\n";
            $basePrompt .= "1. Les documents ci-dessus sont ta SOURCE PRINCIPALE d'information.\n";
            $basePrompt .= "2. Si un document contient une information même partiellement liée à la question, UTILISE-LE et cite-le.\n";
            $basePrompt .= "3. Analyse TOUS les documents fournis avant de dire que l'information n'est pas disponible.\n";
            $basePrompt .= "4. Si tu trouves un document pertinent (même si le titre n'est pas exactement identique à la question), UTILISE son contenu.\n";
            $basePrompt .= "5. EXEMPLES:\n";
            $basePrompt .= "   - Question: 'répertoire des centres de gestion' → Document: 'REPERTOIRE DES CENTRES DE GESTION' → UTILISE-LE!\n";
            $basePrompt .= "   - Question: 'loi sur les contrats' → Document: 'Code des obligations' → UTILISE-LE!\n";
            $basePrompt .= "   - Question: 'impôts' → Document: 'Ressources fiscales' → UTILISE-LE!\n";
            $basePrompt .= "6. Ne dis JAMAIS 'Je n'ai pas cette information' si des documents pertinents sont fournis ci-dessus.\n";
            $basePrompt .= "7. Si tu utilises un document, commence ta réponse par le contenu du document, pas par 'Je n'ai pas...'.\n\n";
            $basePrompt .= "8. Si VRAIMENT aucun document ne correspond, alors utilise tes connaissances juridiques générales.";
        } else {
            $basePrompt .= "Réponds en fonction de tes connaissances juridiques générales du droit africain.";
        }

        return $basePrompt;
    }

    /**
     * Count tokens in text (approximation)
     * 
     * @param string $text
     * @return int Estimated tokens
     */
    public function countTokens(string $text): int
    {
        // Rough approximation: 1 token ≈ 4 characters for French
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Set model parameters
     * 
     * @param string $model Model name
     * @param int $maxTokens Max tokens for completion
     * @param float $temperature Temperature (0-2)
     * @return self
     */
    public function setParameters(string $model = null, int $maxTokens = null, float $temperature = null): self
    {
        if ($model !== null) {
            $this->defaultModel = $model;
        }

        if ($maxTokens !== null) {
            $this->maxTokens = $maxTokens;
        }

        if ($temperature !== null) {
            $this->temperature = max(0, min(2, $temperature));
        }

        return $this;
    }

    /**
     * Get available models
     * 
     * @return array List of available models
     */
    public function getAvailableModels(): array
    {
        return [
            'gpt-3.5-turbo' => [
                'name' => 'GPT-3.5 Turbo',
                'max_tokens' => 4096,
                'cost_per_1k_tokens' => ['prompt' => 0.0015, 'completion' => 0.002],
            ],
            'gpt-4' => [
                'name' => 'GPT-4',
                'max_tokens' => 8192,
                'cost_per_1k_tokens' => ['prompt' => 0.03, 'completion' => 0.06],
            ],
            'gpt-4-turbo-preview' => [
                'name' => 'GPT-4 Turbo',
                'max_tokens' => 128000,
                'cost_per_1k_tokens' => ['prompt' => 0.01, 'completion' => 0.03],
            ],
        ];
    }

    /**
     * Validate API key
     * 
     * @return bool True if API key is valid
     */
    public function validateApiKey(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get('https://api.openai.com/v1/models');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("API key validation error: " . $e->getMessage());
            return false;
        }
    }
}
