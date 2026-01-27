<?php

namespace App\Mail;

use App\Models\ToDo;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TaskCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $task;
    public $user;
    public $bccUsers;
    public $caseName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(ToDo $task, User $user, Collection $bccUsers = null, $caseName = null)
    {
        $this->task = $task;
        $this->user = $user;
        $this->bccUsers = $bccUsers ?? collect();
        $this->caseName = $caseName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $dueDate = !empty($this->task->due_date) ? \Carbon\Carbon::parse($this->task->due_date)->format('d/m/Y') : 'Non définie';
        
        return $this->subject('Nouvelle Tâche Créée - ' . $this->task->description)
                    ->view('emails.task-created')
                    ->with([
                        'userName' => $this->user->name,
                        'taskDescription' => $this->task->description,
                        'taskPriority' => $this->task->priority ?? 'normale',
                        'dueDate' => $dueDate,
                        'taskStatus' => $this->task->status ?? 'en attente',
                        'caseName' => $this->caseName,
                        'bccRecipients' => $this->bccUsers->map(function ($user) {
                            return [
                                'id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email,
                                'type' => $user->type ?? 'utilisateur'
                            ];
                        })->toArray(),
                    ]);
    }
}
