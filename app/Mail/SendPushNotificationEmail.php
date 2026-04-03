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
    public $renderedBody;
    
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
        $this->renderedBody = $this->normalizeBodyForEmail((string) ($notification->body ?? ''));
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
                'renderedBody' => $this->renderedBody,
                ...$this->emailData
            ]);
    }

    /**
     * Normalize HTML entered from WYSIWYG code mode so email clients render it correctly.
     */
    private function normalizeBodyForEmail(string $rawBody): string
    {
        $html = trim(htmlspecialchars_decode($rawBody, ENT_QUOTES | ENT_HTML5));

        if ($html === '') {
            return '';
        }

        // If user pasted a full HTML document, keep only <body> inner content.
        if (preg_match('/<body\b[^>]*>(.*?)<\/body>/is', $html, $matches) === 1) {
            $html = trim($matches[1]);
        }

        // Remove full head/title/script/style blocks when present.
        $html = preg_replace('/<\s*head\b[^>]*>.*?<\s*\/\s*head\s*>/is', '', $html) ?? $html;
        $html = preg_replace('/<\s*title\b[^>]*>.*?<\s*\/\s*title\s*>/is', '', $html) ?? $html;
        $html = preg_replace('/<\s*style\b[^>]*>.*?<\s*\/\s*style\s*>/is', '', $html) ?? $html;
        $html = preg_replace('/<\s*script\b[^>]*>.*?<\s*\/\s*script\s*>/is', '', $html) ?? $html;

        // Remove document-level tags that can break rendering in email body blocks.
        $html = preg_replace('/<!doctype[^>]*>/i', '', $html) ?? $html;
        $html = preg_replace('/<\/?\s*(html|head|meta|title|base|link)\b[^>]*>/i', '', $html) ?? $html;

        $html = trim($html);

        // Plain text fallback when no HTML tags remain.
        if ($html === strip_tags($html)) {
            return nl2br(e($html));
        }

        return $html;
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
