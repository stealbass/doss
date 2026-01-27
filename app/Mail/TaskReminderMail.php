<?php

namespace App\Mail;

use App\Models\ToDo;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaskReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $task;
    public $user;
    public $daysRemaining;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(ToDo $task, User $user, $daysRemaining)
    {
        $this->task = $task;
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
        $dueDate = \Carbon\Carbon::parse($this->task->due_date)->format('d/m/Y');
        
        return $this->subject('Rappel: Tâche à échéance dans ' . abs($this->daysRemaining) . ' jour(s)')
                    ->view('emails.task-reminder')
                    ->with([
                        'userName' => $this->user->name,
                        'taskDescription' => $this->task->description,
                        'taskPriority' => $this->task->priority ?? 'normale',
                        'dueDate' => $dueDate,
                        'daysRemaining' => abs($this->daysRemaining),
                        'taskStatus' => $this->task->status ?? 'en attente',
                    ]);
    }
}
