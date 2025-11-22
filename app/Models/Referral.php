<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_user_id',
        'referred_user_id',
        'referral_code',
        'referred_email',
        'status',
        'registered_at',
        'completed_at',
        'expires_at',
        'source',
        'campaign',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Relation: Parrain (celui qui invite)
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * Relation: Filleul (celui qui est invité)
     */
    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    /**
     * Scope: Parrainages réussis (abonnement payant activé)
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Parrainages en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Parrainages expirés
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                     ->orWhere(function($q) {
                         $q->where('expires_at', '<=', now())
                           ->whereIn('status', ['pending', 'registered']);
                     });
    }

    /**
     * Génère un code de parrainage unique
     */
    public static function generateUniqueCode()
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Marque le parrainage comme enregistré (compte créé)
     */
    public function markAsRegistered($userId)
    {
        $this->update([
            'referred_user_id' => $userId,
            'status' => 'registered',
            'registered_at' => now(),
        ]);
    }

    /**
     * Marque le parrainage comme réussi (abonnement payant)
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Incrémente le compteur du parrain
        $this->referrer->increment('successful_referrals_count');

        // Vérifie si le parrain atteint 10 parrainages pour récompense
        $this->checkForReward();
    }

    /**
     * Vérifie et crée une récompense si 10 parrainages atteints
     */
    protected function checkForReward()
    {
        $completedCount = self::where('referrer_user_id', $this->referrer_user_id)
                              ->completed()
                              ->count();

        if ($completedCount % 10 === 0) {
            ReferralReward::create([
                'user_id' => $this->referrer_user_id,
                'reward_type' => 'free_month',
                'value' => 1,
                'description' => 'Félicitations ! Vous avez parrainé ' . $completedCount . ' utilisateurs payants.',
                'referrals_required' => 10,
                'referrals_completed' => 10,
                'status' => 'earned',
                'earned_at' => now(),
                'expires_at' => now()->addYear(),
            ]);
        }
    }

    /**
     * Vérifie si le parrainage est expiré
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at <= now();
    }
}
