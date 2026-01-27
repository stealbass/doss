<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendBillEmail extends Mailable
{
    use Queueable, SerializesModels;
    
    public $bill;
    public $emailData;
    public $customSubject;
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($bill, $emailData, $customSubject)
    {
        $this->bill = $bill;
        $this->emailData = $emailData;
        $this->customSubject = $customSubject;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->customSubject)
            ->view('email.bill_send')
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
