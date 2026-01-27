<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'attached_documents',
        'rag_context',
        'rag_documents_count',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'ai_model',
        'is_helpful',
        'feedback_comment',
    ];

    protected $casts = [
        'attached_documents' => 'array',
        'rag_context' => 'array',
        'is_helpful' => 'boolean',
    ];

    /**
     * Relation: Appartient à une conversation
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Relation: Documents attachés à ce message
     */
    public function submittedDocuments()
    {
        return $this->hasMany(SubmittedDocument::class);
    }

    /**
     * Scope: Messages de l'utilisateur
     */
    public function scopeUserMessages($query)
    {
        return $query->where('role', 'user');
    }

    /**
     * Scope: Réponses de l'assistant
     */
    public function scopeAssistantMessages($query)
    {
        return $query->where('role', 'assistant');
    }

    /**
     * Scope: Messages avec feedback
     */
    public function scopeWithFeedback($query)
    {
        return $query->whereNotNull('is_helpful');
    }

    /**
     * Vérifie si le message est de l'utilisateur
     */
    public function isUserMessage()
    {
        return $this->role === 'user';
    }

    /**
     * Vérifie si le message utilise le contexte RAG
     */
    public function hasRagContext()
    {
        return !empty($this->rag_context) && $this->rag_documents_count > 0;
    }

    /**
     * Calcule le coût estimé en USD basé sur les tokens
     */
    public function estimatedCost()
    {
        // Prix OpenAI (approximatifs)
        $costs = [
            'gpt-3.5-turbo' => ['prompt' => 0.0015 / 1000, 'completion' => 0.002 / 1000],
            'gpt-4' => ['prompt' => 0.03 / 1000, 'completion' => 0.06 / 1000],
            'gpt-4-turbo' => ['prompt' => 0.01 / 1000, 'completion' => 0.03 / 1000],
        ];

        $model = $this->ai_model ?? 'gpt-3.5-turbo';
        $rates = $costs[$model] ?? $costs['gpt-3.5-turbo'];

        return ($this->prompt_tokens * $rates['prompt']) + 
               ($this->completion_tokens * $rates['completion']);
    }
}
