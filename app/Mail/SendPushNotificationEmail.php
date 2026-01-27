<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendPushNotificationEmail extends Mailable
{
    use Queueable, SerializesModels;
    
    public $notification;
    public $user;
    public $emailData;
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($notification, $user, $emailData = [])
    {
        $this->notification = $notification;
        $this->user = $user;
        $this->emailData = $emailData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->notification->title)
            ->view('email.push_notification')
            ->with([
                'notification' => $this->notification,
                'user' => $this->user,
                ...$this->emailData
            ]);
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
