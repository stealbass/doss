<?php

namespace App\Mail;

use App\Models\Hearing;
use App\Models\Cases;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HearingReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $hearing;
    public $case;
    public $user;
    public $daysRemaining;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Hearing $hearing, Cases $case, User $user, $daysRemaining)
    {
        $this->hearing = $hearing;
        $this->case = $case;
        $this->user = $user;
        $this->daysRemaining = $daysRemaining;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $hearingDate = \Carbon\Carbon::parse($this->hearing->date)->format('d/m/Y');
        
        return $this->subject('Rappel: Audience dans ' . abs($this->daysRemaining) . ' jour(s) - ' . $this->case->title)
                    ->view('emails.hearing-reminder')
                    ->with([
                        'userName' => $this->user->name,
                        'caseTitle' => $this->case->title,
                        'caseNumber' => $this->case->case_number,
                        'hearingDate' => $hearingDate,
                        'daysRemaining' => abs($this->daysRemaining),
                        'remarks' => $this->hearing->remarks ?? 'Aucune remarque',
                    ]);
    }
}
