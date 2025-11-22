<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'summary',
        'source',
        'messages_count',
        'total_tokens_used',
        'ai_model',
        'is_archived',
        'is_favorite',
        'last_message_at',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'is_favorite' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    /**
     * Relation: Appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation: A plusieurs messages
     */
    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    /**
     * Relation: Documents soumis dans cette conversation
     */
    public function submittedDocuments()
    {
        return $this->hasMany(SubmittedDocument::class);
    }

    /**
     * Scope: Conversations non archivées
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope: Conversations favorites
     */
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Scope: Par source (mobile ou web)
     */
    public function scopeFromSource($query, $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Génère automatiquement un titre basé sur le premier message
     */
    public function generateTitle()
    {
        $firstUserMessage = $this->messages()->where('role', 'user')->first();
        
        if ($firstUserMessage) {
            // Prendre les 50 premiers caractères
            $title = substr($firstUserMessage->content, 0, 50);
            if (strlen($firstUserMessage->content) > 50) {
                $title .= '...';
            }
            
            $this->update(['title' => $title]);
        }
    }

    /**
     * Met à jour le compteur de messages
     */
    public function updateMessageCount()
    {
        $this->update([
            'messages_count' => $this->messages()->count(),
            'last_message_at' => now(),
        ]);
    }

    /**
     * Met à jour le total de tokens utilisés
     */
    public function updateTokensUsed()
    {
        $totalTokens = $this->messages()->sum('total_tokens');
        $this->update(['total_tokens_used' => $totalTokens]);
    }
}
