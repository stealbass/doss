<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileAppPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_fr',
        'price_monthly',
        'price_yearly',
        'searches_limit',
        'ai_analyses_limit',
        'pdf_downloads_limit',
        'has_full_history',
        'has_advanced_ai',
        'ai_model',
        'max_tokens',
        'is_active',
    ];

    protected $casts = [
        'has_full_history' => 'boolean',
        'has_advanced_ai' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relation: Un plan peut avoir plusieurs abonnements
     */
    public function subscriptions()
    {
        return $this->hasMany(MobileAppSubscription::class);
    }

    /**
     * Relation: Un plan peut avoir plusieurs paiements
     */
    public function payments()
    {
        return $this->hasMany(MobileAppPayment::class);
    }

    /**
     * Scope: Plans actifs seulement
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Accesseur: Prix annuel avec 1 mois offert (au lieu de price_yearly)
     */
    public function getYearlyPriceWithDiscountAttribute()
    {
        return $this->price_monthly * 11; // 11 mois au lieu de 12
    }

    /**
     * Vérifie si le plan est gratuit
     */
    public function isFree()
    {
        return $this->name === 'free';
    }

    /**
     * Vérifie si le plan a une limite illimitée pour une fonctionnalité
     */
    public function hasUnlimited($feature)
    {
        $limitField = $feature . '_limit';
        return isset($this->$limitField) && $this->$limitField === -1;
    }
}
