<?php

namespace App\Models;

use App\Mail\QuotaAlertMail;
use App\Models\Utility;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
        'messages_sent_today',
        'messages_last_reset_date',
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

        // Clear cached alert flags for the new period
        $this->clearAlertCache('searches');
        $this->clearAlertCache('ai_analyses');
        $this->clearAlertCache('pdf_downloads');
    }

    /**
     * Check if subscription can search
     */
    public function canSearch()
    {
        if (!$this->plan) {
            return false;
        }
        if ($this->plan->searches_limit === -1) {
            return true; // unlimited
        }
        return $this->searches_used < $this->plan->searches_limit;
    }

    /**
     * Increment search usage
     */
    public function incrementSearch()
    {
        $this->increment('searches_used');

        return $this->dispatchQuotaAlerts('searches');
    }

    /**
     * Check if subscription can use AI analysis
     */
    public function canUseAIAnalysis()
    {
        if (!$this->plan) {
            return false;
        }
        
        // Si le plan a une limite explicite (pas -1)
        if ($this->plan->ai_analyses_limit !== -1) {
            return $this->ai_analyses_used < $this->plan->ai_analyses_limit;
        }
        
        // Plan "illimité" (-1) mais avec fair-use cap
        if ($this->plan->fair_use_monthly_cap !== null) {
            return $this->ai_analyses_used < $this->plan->fair_use_monthly_cap;
        }
        
        // Vraiment illimité (pas de limit, pas de fair-use)
        return true;
    }

    /**
     * Increment AI analysis usage
     */
    public function incrementAIAnalysis()
    {
        $this->increment('ai_analyses_used');

        return $this->dispatchQuotaAlerts('ai_analyses');
    }

    /**
     * Check if subscription can download PDFs
     */
    public function canDownloadPDF()
    {
        if (!$this->plan) {
            return false;
        }
        if ($this->plan->pdf_downloads_limit === -1) {
            return true; // unlimited
        }
        return $this->pdf_downloads_used < $this->plan->pdf_downloads_limit;
    }

    /**
     * Increment PDF download usage
     */
    public function incrementPDFDownload()
    {
        $this->increment('pdf_downloads_used');

        return $this->dispatchQuotaAlerts('pdf_downloads');
    }

    /**
     * Check if subscription can send message today
     */
    public function canSendMessage()
    {
        if (!$this->plan) {
            return false;
        }

        // Unlimited messages
        if ($this->plan->messages_per_day_limit === -1) {
            return true;
        }

        // Reset counter if new day
        $today = now()->toDateString();
        if ($this->messages_last_reset_date !== $today) {
            $this->messages_sent_today = 0;
            $this->messages_last_reset_date = $today;
            $this->saveQuietly(); // Save without triggering events
        }

        return $this->messages_sent_today < $this->plan->messages_per_day_limit;
    }

    /**
     * Increment message count
     */
    public function incrementMessage()
    {
        // Reset if new day
        $today = now()->toDateString();
        if ($this->messages_last_reset_date !== $today) {
            $this->messages_sent_today = 1;
            $this->messages_last_reset_date = $today;
        } else {
            $this->increment('messages_sent_today');
        }

        $this->save();
    }

    /**
     * Calcule les jours restants
     */
    public function daysRemaining()
    {
        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Envoie les alertes d'usage (80% et 100%) par email et pour l'app.
     * Retourne un tableau d'alerte pour affichage in-app si nécessaire.
     */
    protected function dispatchQuotaAlerts(string $feature): ?array
    {
        if (!$this->plan) {
            return null;
        }

        $limitField = $feature . '_limit';
        $usedField = $feature . '_used';

        $limit = $this->plan->$limitField ?? 0;
        if ($limit === -1 || $limit <= 0) {
            return null; // illimité ou non configuré
        }

        $used = $this->$usedField;
        $percentage = $limit > 0 ? ($used / $limit) * 100 : 0;

        $alerts = [];
        foreach ([80, 100] as $threshold) {
            if ($percentage >= $threshold && !$this->hasAlertBeenSent($feature, $threshold)) {
                $this->markAlertSent($feature, $threshold);
                $message = $this->buildAlertMessage($feature, $threshold, $limit, $used);
                $this->sendQuotaAlertEmail($feature, $threshold, $limit, $used);
                $alerts[] = [
                    'threshold' => $threshold,
                    'feature' => $feature,
                    'message' => $message,
                    'limit' => $limit,
                    'used' => $used,
                    'remaining' => max(0, $limit - $used),
                    'reset_at' => $this->quota_reset_at?->toIso8601String(),
                ];
            }
        }

        return !empty($alerts) ? $alerts : null;
    }

    private function hasAlertBeenSent(string $feature, int $threshold): bool
    {
        $key = $this->getAlertCacheKey($feature, $threshold);
        return Cache::has($key);
    }

    private function markAlertSent(string $feature, int $threshold): void
    {
        $key = $this->getAlertCacheKey($feature, $threshold);
        $ttlSeconds = now()->endOfMonth()->endOfDay()->diffInSeconds(now());
        Cache::put($key, true, $ttlSeconds > 0 ? $ttlSeconds : 3600);
    }

    private function clearAlertCache(string $feature): void
    {
        foreach ([80, 100] as $threshold) {
            Cache::forget($this->getAlertCacheKey($feature, $threshold));
        }
    }

    private function getAlertCacheKey(string $feature, int $threshold): string
    {
        $monthKey = now()->format('Ym');
        return "quota_alert:{$feature}:{$this->id}:{$threshold}:{$monthKey}";
    }

    private function buildAlertMessage(string $feature, int $threshold, int $limit, int $used): string
    {
        $labels = [
            'ai_analyses' => 'analyses IA',
            'searches' => 'recherches',
            'pdf_downloads' => 'téléchargements PDF',
        ];

        $label = $labels[$feature] ?? $feature;
        $remaining = max(0, $limit - $used);

        if ($threshold >= 100) {
            return "Votre quota de {$label} est épuisé. Mettez à niveau votre plan pour continuer.";
        }

        return "Vous avez utilisé {$threshold}% de votre quota de {$label}. Il reste {$remaining} actions disponibles.";
    }

    private function sendQuotaAlertEmail(string $feature, int $threshold, int $limit, int $used): void
    {
        try {
            $this->loadMissing('user');
            if (!$this->user || !$this->user->email) {
                return;
            }

            // Configure SMTP from database settings (same pattern as push notifications)
            $ownerId = $this->user->created_by ?? $this->user_id;
            try {
                Utility::getSMTPDetails($ownerId);
            } catch (\Exception $e) {
                // Continue without email if SMTP misconfigured
                \Log::warning('SMTP config missing for quota alert: ' . $e->getMessage());
                return;
            }

            Mail::to($this->user->email)->send(new QuotaAlertMail($this, $feature, $threshold, $limit, $used));
        } catch (\Exception $e) {
            \Log::warning('Quota alert email failed', [
                'subscription_id' => $this->id,
                'feature' => $feature,
                'threshold' => $threshold,
                'error' => $e->getMessage(),
            ]);
        }
    }
}