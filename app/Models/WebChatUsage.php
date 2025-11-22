<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebChatUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'monthly_quota',
        'requests_used',
        'requests_remaining',
        'quota_month',
        'quota_reset_at',
        'total_tokens_used',
        'last_request_at',
        'alert_80_percent_sent',
        'alert_100_percent_sent',
    ];

    protected $casts = [
        'quota_month' => 'date',
        'quota_reset_at' => 'datetime',
        'last_request_at' => 'datetime',
        'alert_80_percent_sent' => 'boolean',
        'alert_100_percent_sent' => 'boolean',
    ];

    /**
     * Relation: Appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifie si l'utilisateur peut faire une requête
     */
    public function canMakeRequest()
    {
        return $this->requests_remaining > 0;
    }

    /**
     * Incrémente l'utilisation
     */
    public function incrementUsage($tokensUsed = 0)
    {
        $this->increment('requests_used');
        $this->decrement('requests_remaining');
        $this->increment('total_tokens_used', $tokensUsed);
        $this->update(['last_request_at' => now()]);

        // Vérifie si alerte à 80% doit être envoyée
        if (!$this->alert_80_percent_sent && $this->getUsagePercentage() >= 80) {
            $this->sendQuotaAlert(80);
            $this->update(['alert_80_percent_sent' => true]);
        }

        // Vérifie si alerte à 100% doit être envoyée
        if (!$this->alert_100_percent_sent && $this->requests_remaining <= 0) {
            $this->sendQuotaAlert(100);
            $this->update(['alert_100_percent_sent' => true]);
        }
    }

    /**
     * Calcule le pourcentage d'utilisation
     */
    public function getUsagePercentage()
    {
        if ($this->monthly_quota == 0) {
            return 0;
        }
        return ($this->requests_used / $this->monthly_quota) * 100;
    }

    /**
     * Réinitialise le quota mensuel
     */
    public function resetQuota()
    {
        $this->update([
            'requests_used' => 0,
            'requests_remaining' => $this->monthly_quota,
            'quota_month' => now()->startOfMonth(),
            'quota_reset_at' => now()->addMonth()->startOfMonth(),
            'total_tokens_used' => 0,
            'alert_80_percent_sent' => false,
            'alert_100_percent_sent' => false,
        ]);
    }

    /**
     * Envoie une alerte de quota
     */
    protected function sendQuotaAlert($percentage)
    {
        // TODO: Implémenter l'envoi de notification
        // Peut être un email, notification push, ou popup dans l'app
        
        if ($percentage >= 100) {
            // Alerte: quota épuisé, inviter à télécharger l'app mobile
        } else {
            // Alerte: 80% du quota utilisé
        }
    }

    /**
     * Obtient ou crée l'enregistrement pour le mois en cours
     */
    public static function getOrCreateForUser($userId, $monthlyQuota)
    {
        $currentMonth = now()->startOfMonth()->toDateString();
        
        return self::firstOrCreate(
            [
                'user_id' => $userId,
                'quota_month' => $currentMonth,
            ],
            [
                'monthly_quota' => $monthlyQuota,
                'requests_used' => 0,
                'requests_remaining' => $monthlyQuota,
                'quota_reset_at' => now()->addMonth()->startOfMonth(),
            ]
        );
    }
}
