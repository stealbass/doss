<?php

namespace App\Mail;

use App\Models\Hearing;
use App\Models\Cases;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HearingCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $hearing;
    public $case;
    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Hearing $hearing, Cases $case, User $user)
    {
        $this->hearing = $hearing;
        $this->case = $case;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $hearingDate = \Carbon\Carbon::parse($this->hearing->date)->format('d/m/Y');
        
        return $this->subject('Nouvelle Audience Créée - ' . $this->case->title)
                    ->view('emails.hearing-created')
                    ->with([
                        'userName' => $this->user->name,
                        'caseTitle' => $this->case->title,
                        'caseNumber' => $this->case->case_number,
                        'hearingDate' => $hearingDate,
                        'remarks' => $this->hearing->remarks ?? 'Aucune remarque',
                    ]);
    }
}
