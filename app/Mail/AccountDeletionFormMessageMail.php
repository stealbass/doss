<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountDeletionFormMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function build()
    {
        $subject = $this->payload['subject'] ?? 'Demande de suppression de compte';

        return $this->subject('[DossyPro] ' . $subject)
            ->view('email.account_deletion_form_message');
    }
}
