<?php

namespace App\Services;

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
    private string $defaultModel = 'gpt-4-turbo-preview';
    private int $maxTokens = 2000;
    private float $temperature = 0.7;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY', ''));
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

        $model = $model ?? $this->defaultModel;

        try {
            // Build messages array
            $messages = $this->buildMessages($userMessage, $context, $conversationHistory);

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
     * @param string $context RAG context
     * @return string System message
     */
    private function buildSystemMessage(string $context): string
    {
        $basePrompt = "Tu es Dossy IA, un assistant juridique intelligent spécialisé dans le droit camerounais. "
            . "Tu aides les avocats, juristes et étudiants en droit avec des réponses précises et fiables. "
            . "Tes réponses doivent être professionnelles, basées sur des faits juridiques, et citées lorsque possible.";

        if (!empty($context)) {
            $basePrompt .= "\n\nUtilise les documents suivants comme contexte pour répondre à la question:\n\n"
                . $context
                . "\n\nSi les documents fournis ne contiennent pas d'information pertinente, "
                . "indique-le clairement et fournis une réponse générale basée sur tes connaissances juridiques.";
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
