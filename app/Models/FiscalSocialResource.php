<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Jobs\ProcessFiscalResourceForRAG;

class FiscalSocialResource extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'description',
        'country',
        'year',
        'language',
        'resource_type',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'issuing_authority',
        'publication_date',
        'effective_date',
        'expiry_date',
        'version',
        'is_latest_version',
        'supersedes_id',
        'is_mobile_visible',
        'allowed_plans',
        'is_premium',
        'ai_context',
        'key_points',
        'downloads_count',
        'views_count',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'publication_date' => 'date',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'is_latest_version' => 'boolean',
        'is_mobile_visible' => 'boolean',
        'is_premium' => 'boolean',
        'allowed_plans' => 'array',
        'key_points' => 'array',
        'downloads_count' => 'integer',
        'views_count' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(ResourceCategory::class, 'category_id');
    }

    public function supersedes()
    {
        return $this->belongsTo(FiscalSocialResource::class, 'supersedes_id');
    }

    public function supersededBy()
    {
        return $this->hasMany(FiscalSocialResource::class, 'supersedes_id');
    }

    public function getCountryInfoAttribute()
    {
        $countries = config('mobile_countries.supported_countries');
        return $countries[$this->country] ?? null;
    }

    public function getFileUrlAttribute()
    {
        $url = \App\Models\Utility::get_file($this->file_path);
        if (!empty($url)) {
            return $url;
        }

        return url('storage/' . $this->file_path);
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeLatestVersion($query)
    {
        return $query->where('is_latest_version', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('resource_type', $type);
    }

    public function scopeMobileVisible($query)
    {
        return $query->where('is_mobile_visible', true);
    }

    /**
     * Format file size in human-readable format
     */
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        if ($bytes === 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    /**
     * Auto-index/reindex on create/update, clean up on delete
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-index when resource is created
        static::created(function ($resource) {
            if (!empty($resource->extracted_text)) {
                ProcessFiscalResourceForRAG::dispatch($resource->id);
            }
        });

        // Auto-reindex when resource is updated with new extracted_text
        static::updated(function ($resource) {
            if ($resource->wasChanged('extracted_text') && !empty($resource->extracted_text)) {
                (new \App\Services\AdvancedRagService())->deleteFiscalResource($resource->id);
                ProcessFiscalResourceForRAG::dispatch($resource->id);
            }
        });

        // Clean up vectors on hard delete
        static::forceDeleting(function ($resource) {
            (new \App\Services\AdvancedRagService())->deleteFiscalResource($resource->id);
        });
    }
}
