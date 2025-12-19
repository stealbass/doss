<?php

namespace App\Jobs;

use App\Models\ToDo;
use App\Models\Cases;
use App\Models\User;
use App\Mail\TaskCreatedMail;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendTaskCreatedNotification implements ShouldQueue
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

            // Send email to each user
            foreach ($usersToNotify as $user) {
                if (!empty($user->email)) {
                    Mail::to($user->email)->send(new TaskCreatedMail($this->task, $user));
                    Log::info("Task created notification sent to: {$user->email}");
                }
            }

            // Send push notifications
            if ($usersToNotify->count() > 0) {
                $pushService = new PushNotificationService();
                $result = $pushService->sendTaskCreatedNotification(
                    $this->task,
                    $usersToNotify->toArray()
                );
                
                if ($result['success']) {
                    Log::info("Push notification sent for task created", [
                        'task_id' => $this->task->id,
                        'users_count' => $usersToNotify->count(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("Error sending task created notification: " . $e->getMessage());
        }
    }
}
