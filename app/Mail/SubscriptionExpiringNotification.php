<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class SubscriptionExpiringNotification extends Mailable
{
    public $user;
    public $emailData;
    
    public function __construct($user, $emailData)
    {
        $this->user = $user;
        $this->emailData = $emailData;
    }

    public function build()
    {
        return $this->subject('⚠️ Votre abonnement Dossy Pro expire bientôt')
            ->view('email.subscription_expiring')
            ->with($this->emailData);
    }
}
