<?php

namespace App\Jobs;

use App\Models\ToDo;
use App\Models\Cases;
use App\Models\User;
use App\Mail\TaskReminderMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendTaskReminderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $task;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ToDo $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Get all users to notify
            $usersToNotify = collect();
            
            // Add task assignee if exists
            if ($this->task->assign) {
                $assignee = User::find($this->task->assign);
                if ($assignee) {
                    $usersToNotify->push($assignee);
                }
            }

            // Add task creator
            $creator = User::find($this->task->created_by);
            if ($creator) {
                $usersToNotify->push($creator);
            }

            // If task has a case, add case-related users
            if ($this->task->related_to == 'case' && $this->task->case_id) {
                $case = Cases::find($this->task->case_id);
                if ($case) {
                    // Add advocates
                    if (!empty($case->advocates)) {
                        $advocateIds = is_array($case->advocates) ? $case->advocates : json_decode($case->advocates, true);
                        if ($advocateIds) {
                            $advocates = User::whereIn('id', $advocateIds)->get();
                            $usersToNotify = $usersToNotify->merge($advocates);
                        }
                    }
                }
            }

            // Remove duplicates
            $usersToNotify = $usersToNotify->unique('id');

            // Calculate days remaining
            $dueDate = \Carbon\Carbon::parse($this->task->due_date);
            $daysRemaining = now()->diffInDays($dueDate, false);

            // Send email to each user
            foreach ($usersToNotify as $user) {
                if (!empty($user->email)) {
                    Mail::to($user->email)->send(new TaskReminderMail($this->task, $user, $daysRemaining));
                    Log::info("Task reminder sent to: {$user->email} ({$daysRemaining} days before due date)");
                }
            }

        } catch (\Exception $e) {
            Log::error("Error sending task reminder notification: " . $e->getMessage());
        }
    }
}
