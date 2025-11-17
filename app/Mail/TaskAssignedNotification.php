<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class TaskAssignedNotification extends Mailable
{
    public $task;
    public $emailData;
    
    public function __construct($task, $emailData)
    {
        $this->task = $task;
        $this->emailData = $emailData;
    }

    public function build()
    {
        return $this->subject('Nouvelle tâche assignée: ' . $this->task->title)
            ->view('email.task_assigned')
            ->with($this->emailData);
    }
}
