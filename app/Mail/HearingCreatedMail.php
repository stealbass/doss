<?php

namespace App\Mail;

use App\Models\Hearing;
use App\Models\Cases;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class HearingCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $hearing;
    public $case;
    public $user;
    public $bccUsers;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Hearing $hearing, Cases $case, User $user, Collection $bccUsers = null)
    {
        $this->hearing = $hearing;
        $this->case = $case;
        $this->user = $user;
        $this->bccUsers = $bccUsers ?? collect();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $hearingDate = \Carbon\Carbon::parse($this->hearing->date)->format('d/m/Y H:i');
        
        return $this->subject('Nouvelle Audience Créée - ' . $this->case->title)
                    ->view('emails.hearing-created')
                    ->with([
                        'userName' => $this->user->name,
                        'caseTitle' => $this->case->title,
                        'caseNumber' => $this->case->case_number,
                        'hearingDate' => $hearingDate,
                        'hearingLocation' => $this->hearing->location ?? 'Non spécifiée',
                        'remarks' => $this->hearing->remarks ?? 'Aucune remarque',
                        'bccRecipients' => $this->bccUsers,
                    ]);
    }
}
