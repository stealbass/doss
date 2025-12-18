<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileSubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'max_searches',
        'max_analyses',
        'max_downloads',
        'audio_transcription',
        'anonymization',
        'multi_accounts',
        'max_sub_accounts',
        'legal_alerts',
        'word_export',
        'priority_support',
        'access_templates',
        'access_fiscal_resources',
        'access_calculators',
        'access_premium_templates',
        'ai_messages_per_month',
        'advanced_ai',
        'badge_color',
        'icon',
        'sort_order',
        'is_popular',
        'is_active',
        'is_visible',
        'has_trial',
        'trial_days',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'max_searches' => 'integer',
        'max_analyses' => 'integer',
        'max_downloads' => 'integer',
        'max_sub_accounts' => 'integer',
        'audio_transcription' => 'boolean',
        'anonymization' => 'boolean',
        'multi_accounts' => 'boolean',
        'legal_alerts' => 'boolean',
        'word_export' => 'boolean',
        'priority_support' => 'boolean',
        'access_templates' => 'boolean',
        'access_fiscal_resources' => 'boolean',
        'access_calculators' => 'boolean',
        'access_premium_templates' => 'boolean',
        'ai_messages_per_month' => 'integer',
        'advanced_ai' => 'boolean',
        'sort_order' => 'integer',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'has_trial' => 'boolean',
        'trial_days' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true)->orderBy('sort_order');
    }

    public function getFormattedPriceAttribute()
    {
        if ($this->price_monthly == 0) {
            return 'Gratuit';
        }
        return number_format($this->price_monthly, 0, ',', ' ') . ' FCFA/mois';
    }

    public function getIsUnlimitedAttribute()
    {
        return $this->max_searches === -1;
    }

    public function hasFeature($feature)
    {
        return $this->{$feature} ?? false;
    }
}
