<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessDocumentForRAG;

class LegalDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'country',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'downloads_count',
        'views_count',
        'created_by',
        'extracted_text',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'downloads_count' => 'integer',
        'views_count' => 'integer',
    ];

    /**
     * Get the category this document belongs to
     */
    public function category()
    {
        return $this->belongsTo(LegalCategory::class, 'category_id');
    }

    /**
     * Get the creator of this document
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Increment download count
     */
    public function incrementDownloads()
    {
        $this->increment('downloads_count');
    }

    /**
     * Increment view count
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Get full file URL
     */
    public function getFileUrlAttribute()
    {
        return Storage::url($this->file_path);
    }

    /**
     * Delete file when document is deleted
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-index when document is created
        static::created(function ($document) {
            if (!empty($document->extracted_text)) {
                ProcessDocumentForRAG::dispatch($document->id);
            }
        });

        // Auto-reindex when document is updated and extracted_text changed
        static::updated(function ($document) {
            // Check if extracted_text was modified
            if ($document->wasChanged('extracted_text') && !empty($document->extracted_text)) {
                // Remove old vectors before reindexing
                (new \App\Services\AdvancedRagService())->deleteDocument($document->id);
                // Reindex with new text
                ProcessDocumentForRAG::dispatch($document->id);
            }
        });

        // Delete vectors from Pinecone when document is deleted
        static::deleting(function ($document) {
            (new \App\Services\AdvancedRagService())->deleteDocument($document->id);
            
            if (Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }
        });
    }
}
