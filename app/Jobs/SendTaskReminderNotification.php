<?php

namespace App\Jobs;

use App\Models\ToDo;
use App\Models\Cases;
use App\Models\User;
use App\Mail\TaskReminderMail;
use App\Models\Utility;
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
            Utility::getSMTPDetails($this->task->created_by);

            // Get all users to notify
            $usersToNotify = collect();
            
            // Add task assignees (handle CSV/JSON/single value)
            if (!empty($this->task->assign_to)) {
                $assignedIds = $this->task->assign_to;

                if (is_string($assignedIds)) {
                    if (str_contains($assignedIds, ',')) {
                        $assignedIds = array_map('trim', explode(',', $assignedIds));
                    } elseif (str_contains($assignedIds, '[')) {
                        $assignedIds = json_decode($assignedIds, true);
                    } else {
                        $assignedIds = [$assignedIds];
                    }
                }

                if (is_numeric($assignedIds)) {
                    $assignedIds = [$assignedIds];
                }

                if (is_array($assignedIds) && !empty($assignedIds)) {
                    $assignedUsers = User::whereIn('id', $assignedIds)->get();
                    $usersToNotify = $usersToNotify->merge($assignedUsers);
                }
            }

            // Add task creator
            $creator = User::find($this->task->created_by);
            if ($creator) {
                $usersToNotify->push($creator);
            }

            // If task has a case, add case-related users
            if ($this->task->relate_to) {
                $case = Cases::find($this->task->relate_to);
                if ($case) {
                    // Add advocates
                    if (!empty($case->advocates)) {
                        $advocateIds = $case->advocates;

                        if (is_string($advocateIds)) {
                            if (str_contains($advocateIds, ',')) {
                                $advocateIds = array_map('trim', explode(',', $advocateIds));
                            } elseif (str_contains($advocateIds, '[')) {
                                $advocateIds = json_decode($advocateIds, true);
                            } else {
                                $advocateIds = [$advocateIds];
                            }
                        }

                        if (is_numeric($advocateIds)) {
                            $advocateIds = [$advocateIds];
                        }

                        if (is_array($advocateIds) && !empty($advocateIds)) {
                            $advocates = User::whereIn('id', $advocateIds)->get();
                            $usersToNotify = $usersToNotify->merge($advocates);
                        }
                    }
                }
            }

            // Remove duplicates and users without email
            $usersToNotify = $usersToNotify
                ->filter(function ($user) {
                    return $user && !empty($user->email);
                })
                ->unique('id');

            // Calculate days remaining
            $dueDate = \Carbon\Carbon::parse($this->task->due_date);
            $daysRemaining = now()->diffInDays($dueDate, false);

            // Send email to each user
            foreach ($usersToNotify as $user) {
                Mail::to($user->email)->send(new TaskReminderMail($this->task, $user, $daysRemaining));
                Log::info("Task reminder sent to: {$user->email} ({$daysRemaining} days before due date)");
            }

        } catch (\Exception $e) {
            Log::error("Error sending task reminder notification: " . $e->getMessage());
        }
    }
}
