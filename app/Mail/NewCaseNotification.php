<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewCaseNotification extends Mailable
{
    use Queueable, SerializesModels;
    
    public $case;
    public $emailData;
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($case, $emailData)
    {
        $this->case = $case;
        $this->emailData = $emailData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Nouvelle affaire créée: ' . $this->case->title)
            ->view('email.new_case')
            ->with($this->emailData);
    }
    
    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
