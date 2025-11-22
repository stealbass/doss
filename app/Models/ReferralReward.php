<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reward_type',
        'value',
        'description',
        'referrals_required',
        'referrals_completed',
        'status',
        'earned_at',
        'redeemed_at',
        'expires_at',
        'mobile_app_subscription_id',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Relation: Appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation: Peut être liée à un abonnement
     */
    public function subscription()
    {
        return $this->belongsTo(MobileAppSubscription::class, 'mobile_app_subscription_id');
    }

    /**
     * Scope: Récompenses gagnées mais non utilisées
     */
    public function scopeEarned($query)
    {
        return $query->where('status', 'earned')
                     ->where(function($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    /**
     * Scope: Récompenses utilisées
     */
    public function scopeRedeemed($query)
    {
        return $query->where('status', 'redeemed');
    }

    /**
     * Scope: Récompenses expirées
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                     ->orWhere(function($q) {
                         $q->where('expires_at', '<=', now())
                           ->where('status', '!=', 'redeemed');
                     });
    }

    /**
     * Utilise la récompense sur un abonnement
     */
    public function redeem($subscriptionId)
    {
        if ($this->status !== 'earned') {
            return false;
        }

        if ($this->isExpired()) {
            $this->update(['status' => 'expired']);
            return false;
        }

        $this->update([
            'status' => 'redeemed',
            'redeemed_at' => now(),
            'mobile_app_subscription_id' => $subscriptionId,
        ]);

        return true;
    }

    /**
     * Vérifie si la récompense est expirée
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at <= now();
    }

    /**
     * Vérifie si la récompense peut être utilisée
     */
    public function canBeRedeemed()
    {
        return $this->status === 'earned' && !$this->isExpired();
    }
}
