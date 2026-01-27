<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class SubmittedDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'conversation_id',
        'message_id',
        'original_filename',
        'stored_filename',
        'storage_path',
        'mime_type',
        'file_size',
        'extracted_text',
        'extracted_text_length',
        'anonymization_detections',
        'has_sensitive_data',
        'detections_count',
        'page_count',
        'metadata',
        'processing_status',
        'processed_at',
        'processing_error',
        'temporary_url',
        'url_expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'anonymization_detections' => 'array',
        'has_sensitive_data' => 'boolean',
        'processed_at' => 'datetime',
        'url_expires_at' => 'datetime',
    ];

    /**
     * Relation: Appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation: Appartient à une conversation
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Relation: Appartient à un message
     */
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Scope: Documents traités avec succès
     */
    public function scopeCompleted($query)
    {
        return $query->where('processing_status', 'completed');
    }

    /**
     * Scope: Documents en cours de traitement
     */
    public function scopeProcessing($query)
    {
        return $query->whereIn('processing_status', ['pending', 'processing']);
    }

    /**
     * Scope: Documents avec erreur
     */
    public function scopeFailed($query)
    {
        return $query->where('processing_status', 'failed');
    }

    /**
     * Génère une URL temporaire (24h)
     */
    public function generateTemporaryUrl()
    {
        $disk = config('filesystems.default');
        $url = Storage::disk($disk)->temporaryUrl(
            $this->storage_path,
            now()->addHours(24)
        );

        $this->update([
            'temporary_url' => $url,
            'url_expires_at' => now()->addHours(24),
        ]);

        return $url;
    }

    /**
     * Récupère l'URL (génère si expirée)
     */
    public function getUrl()
    {
        if (!$this->temporary_url || $this->url_expires_at <= now()) {
            return $this->generateTemporaryUrl();
        }

        return $this->temporary_url;
    }

    /**
     * Marque le document comme traité
     */
    public function markAsProcessed($extractedText, $metadata = [])
    {
        $this->update([
            'processing_status' => 'completed',
            'processed_at' => now(),
            'extracted_text' => $extractedText,
            'extracted_text_length' => strlen($extractedText),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Marque le document comme échoué
     */
    public function markAsFailed($error)
    {
        $this->update([
            'processing_status' => 'failed',
            'processing_error' => $error,
        ]);
    }

    /**
     * Get file URL from R2/S3/Wasabi or local storage (like DocumentTemplate, LegalDocument)
     * Uses Utility::get_file() to handle all storage backends consistently
     */
    public function getFileUrlAttribute()
    {
        if (empty($this->storage_path)) {
            return null;
        }
        return Utility::get_file($this->storage_path);
    }

    /**
     * Taille du fichier formatée
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
