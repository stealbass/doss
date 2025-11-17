<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AdminSubscriptionNotification extends Mailable
{
    public $user;
    public $plan;
    public $emailData;
    public $type; // 'new' or 'expiring'
    
    public function __construct($user, $plan, $emailData, $type = 'new')
    {
        $this->user = $user;
        $this->plan = $plan;
        $this->emailData = $emailData;
        $this->type = $type;
    }

    public function build()
    {
        $subject = $this->type === 'new' 
            ? '🎉 Nouveau abonnement Dossy Pro' 
            : '⚠️ Abonnement sur le point d\'expirer';
            
        return $this->subject($subject)
            ->view('email.admin_subscription')
            ->with($this->emailData);
    }
}
