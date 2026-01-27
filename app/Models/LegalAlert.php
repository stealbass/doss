<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegalAlert extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'alert_type',
        'country',
        'affected_sectors',
        'priority',
        'summary',
        'impact_analysis',
        'action_items',
        'source_url',
        'attachments',
        'published_at',
        'expires_at',
        'is_published',
        'send_email',
        'send_whatsapp',
        'send_push',
        'created_by',
    ];

    protected $casts = [
        'affected_sectors' => 'array',
        'action_items' => 'array',
        'attachments' => 'array',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_published' => 'boolean',
        'send_email' => 'boolean',
        'send_whatsapp' => 'boolean',
        'send_push' => 'boolean',
    ];

    public function recipients()
    {
        return $this->hasMany(LegalAlertRecipient::class);
    }

    public function getCountryInfoAttribute()
    {
        if (!$this->country) return null;
        $countries = config('mobile_countries.supported_countries');
        return $countries[$this->country] ?? null;
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                    ->where('published_at', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country)->orWhereNull('country');
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('alert_type', $type);
    }
}
