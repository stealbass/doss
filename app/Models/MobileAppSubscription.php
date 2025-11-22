<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MobileAppSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mobile_app_plan_id',
        'billing_cycle',
        'status',
        'started_at',
        'expires_at',
        'cancelled_at',
        'next_billing_date',
        'searches_used',
        'ai_analyses_used',
        'pdf_downloads_used',
        'quota_reset_at',
        'payment_reference',
        'amount_paid',
        'auto_renew',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'next_billing_date' => 'datetime',
        'quota_reset_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    /**
     * Relation: Appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation: Appartient à un plan
     */
    public function plan()
    {
        return $this->belongsTo(MobileAppPlan::class, 'mobile_app_plan_id');
    }

    /**
     * Relation: Peut avoir plusieurs paiements
     */
    public function payments()
    {
        return $this->hasMany(MobileAppPayment::class);
    }

    /**
     * Scope: Abonnements actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('expires_at', '>', now());
    }

    /**
     * Scope: Abonnements expirés
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
                     ->where('status', '!=', 'cancelled');
    }

    /**
     * Vérifie si l'abonnement est actif
     */
    public function isActive()
    {
        return $this->status === 'active' && $this->expires_at > now();
    }

    /**
     * Vérifie si une fonctionnalité peut encore être utilisée
     */
    public function canUseFeature($feature)
    {
        $usedField = $feature . '_used';
        $limitField = $feature . '_limit';
        
        // Plan illimité
        if ($this->plan->$limitField === -1) {
            return true;
        }
        
        return $this->$usedField < $this->plan->$limitField;
    }

    /**
     * Incrémente l'utilisation d'une fonctionnalité
     */
    public function incrementUsage($feature)
    {
        $usedField = $feature . '_used';
        $this->increment($usedField);
    }

    /**
     * Réinitialise les quotas mensuels
     */
    public function resetQuota()
    {
        $this->update([
            'searches_used' => 0,
            'ai_analyses_used' => 0,
            'pdf_downloads_used' => 0,
            'quota_reset_at' => now()->addMonth(),
        ]);
    }

    /**
     * Calcule les jours restants
     */
    public function daysRemaining()
    {
        return now()->diffInDays($this->expires_at, false);
    }
}
