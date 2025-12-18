<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'country',
        'language',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'template_type',
        'allowed_plans',
        'is_premium',
        'is_mobile_visible',
        'downloads_count',
        'views_count',
        'variables',
        'ai_context',
        'created_by',
    ];

    protected $casts = [
        'allowed_plans' => 'array',
        'variables' => 'array',
        'is_premium' => 'boolean',
        'is_mobile_visible' => 'boolean',
        'downloads_count' => 'integer',
        'views_count' => 'integer',
    ];

    /**
     * Get the category
     */
    public function category()
    {
        return $this->belongsTo(TemplateCategory::class, 'category_id');
    }

    /**
     * Get tags
     */
    public function tags()
    {
        return $this->belongsToMany(TemplateTag::class, 'document_template_tag');
    }

    /**
     * Get country information
     */
    public function getCountryInfoAttribute()
    {
        if (!$this->country) return null;
        
        $countries = config('mobile_countries.supported_countries');
        return $countries[$this->country] ?? null;
    }

    /**
     * Get file URL
     */
    public function getFileUrlAttribute()
    {
        return url('storage/' . $this->file_path);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute()
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Increment downloads count
     */
    public function incrementDownloads()
    {
        $this->increment('downloads_count');
    }

    /**
     * Increment views count
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Check if accessible by plan
     */
    public function isAccessibleByPlan($userPlan)
    {
        if (!$this->is_premium) return true;
        if (!$this->allowed_plans) return true;
        
        return in_array($userPlan, $this->allowed_plans);
    }

    /**
     * Scope: filter by country
     */
    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country)->orWhereNull('country');
    }

    /**
     * Scope: mobile visible
     */
    public function scopeMobileVisible($query)
    {
        return $query->where('is_mobile_visible', true);
    }

    /**
     * Scope: by template type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('template_type', $type);
    }

    /**
     * Scope: accessible by plan
     */
    public function scopeAccessibleByPlan($query, $plan)
    {
        return $query->where(function($q) use ($plan) {
            $q->where('is_premium', false)
              ->orWhereJsonContains('allowed_plans', $plan)
              ->orWhereNull('allowed_plans');
        });
    }
}
