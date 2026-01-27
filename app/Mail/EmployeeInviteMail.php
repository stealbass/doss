<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeeInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $employee;
    public $plainPassword;
    public $loginUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $employee, string $plainPassword, string $loginUrl)
    {
        $this->employee = $employee;
        $this->plainPassword = $plainPassword;
        $this->loginUrl = $loginUrl;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Votre compte employé a été créé')
                    ->view('emails.employee-invite')
                    ->with([
                        'name' => $this->employee->name,
                        'email' => $this->employee->email,
                        'plainPassword' => $this->plainPassword,
                        'loginUrl' => $this->loginUrl,
                    ]);
    }
}
