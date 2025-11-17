<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class SubscriptionConfirmation extends Mailable
{
    public $user;
    public $plan;
    public $emailData;
    
    public function __construct($user, $plan, $emailData)
    {
        $this->user = $user;
        $this->plan = $plan;
        $this->emailData = $emailData;
    }

    public function build()
    {
        return $this->subject('✅ Confirmation de votre abonnement Dossy Pro')
            ->view('email.subscription_confirmation')
            ->with($this->emailData);
    }
}
