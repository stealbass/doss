<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneratedDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'conversation_id',
        'message_id',
        'template_id',
        'template_name',
        'document_content',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'extracted_variables',
        'status',
        'generation_prompt',
        'generated_by',
    ];

    protected $casts = [
        'extracted_variables' => 'array',
        'generated_by' => 'array', // ['model' => 'gpt-4o-mini', 'tokens' => 2500]
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the conversation
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the message
     */
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the template
     */
    public function template()
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    /**
     * Get file URL
     */
    public function getFileUrlAttribute()
    {
        if (empty($this->file_path)) {
            return null;
        }

        // Try cloud storage first
        $url = Utility::get_file($this->file_path);
        if (!empty($url)) {
            return $url;
        }

        // Fallback to local public storage
        return asset('storage/' . $this->file_path);
    }

    /**
     * Get download URL for API
     */
    public function getDownloadUrlAttribute()
    {
        return route('api.mobile.generated-document.download', $this->id);
    }

    /**
     * Mark as downloaded
     */
    public function markAsDownloaded()
    {
        $this->update([
            'status' => 'downloaded',
            'downloaded_at' => now(),
        ]);
    }

    /**
     * Get readable status
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'En attente',
            'generating' => 'En cours de génération',
            'generated' => 'Généré',
            'failed' => 'Échec',
            'downloaded' => 'Téléchargé',
            'expired' => 'Expiré',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Check if document is ready to download
     */
    public function canDownload(): bool
    {
        return $this->status === 'generated' && !empty($this->file_path);
    }

    /**
     * Check if document has expired (7 days)
     */
    public function isExpired(): bool
    {
        return $this->created_at->addDays(7)->isPast();
    }
}
