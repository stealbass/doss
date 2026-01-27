<?php

namespace App\Mail;

use App\Models\MobileAppSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotaAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public MobileAppSubscription $subscription;
    public string $feature;
    public int $threshold;
    public int $limit;
    public int $used;

    public function __construct(MobileAppSubscription $subscription, string $feature, int $threshold, int $limit, int $used)
    {
        $this->subscription = $subscription;
        $this->feature = $feature;
        $this->threshold = $threshold;
        $this->limit = $limit;
        $this->used = $used;
    }

    public function build()
    {
        $labels = [
            'ai_analyses' => 'Analyses IA',
            'searches' => 'Recherches',
            'pdf_downloads' => 'Téléchargements PDF',
        ];

        $label = $labels[$this->feature] ?? $this->feature;
        $remaining = max(0, $this->limit - $this->used);
        $subject = "Alerte quota {$this->threshold}% - {$label}";

        return $this->subject($subject)
            ->view('emails.quota_alert')
            ->with([
                'user' => $this->subscription->user,
                'label' => $label,
                'threshold' => $this->threshold,
                'limit' => $this->limit,
                'used' => $this->used,
                'remaining' => $remaining,
                'resetAt' => $this->subscription->quota_reset_at,
            ]);
    }
}
