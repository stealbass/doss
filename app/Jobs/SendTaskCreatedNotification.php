<?php

namespace App\Jobs;

use App\Models\ToDo;
use App\Models\Cases;
use App\Models\User;
use App\Mail\TaskCreatedMail;
use App\Services\PushNotificationService;
use App\Models\Utility;
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
            // Load SMTP configuration from database for multi-tenant support
            Utility::getSMTPDetails($this->task->created_by);

            Log::info('SendTaskCreatedNotification job started', [
                'task_id' => $this->task->id,
                'assign_to' => $this->task->assign_to,
                'relate_to' => $this->task->relate_to,
                'created_by' => $this->task->created_by,
            ]);

            // Primary recipient: the user who created the task
            $creator = User::find($this->task->created_by);
            if (!$creator || empty($creator->email)) {
                Log::error('Task creator not found or has no email', ['task_id' => $this->task->id, 'created_by' => $this->task->created_by]);
                return;
            }

            // Collect BCC recipients
            $bccUsers = collect();

            // Add assigned users from task->assign_to (comma-separated)
            if (!empty($this->task->assign_to)) {
                $assignedIds = $this->task->assign_to;

                // Handle different formats
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
                    $assignedUsers = User::whereIn('id', $assignedIds)->where('id', '!=', $this->task->created_by)->get();
                    $bccUsers = $bccUsers->merge($assignedUsers);
                }
            }

            // If task relates to a case, add case clients and advocates
            if (!empty($this->task->relate_to)) {
                $case = Cases::find($this->task->relate_to);

                if ($case) {
                    // Add advocates from case
                    if (!empty($case->advocates_id)) {
                        $advocateIds = $case->advocates_id;

                        // Handle different formats
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
                            $advocates = User::whereIn('id', $advocateIds)->where('id', '!=', $this->task->created_by)->get();
                            $bccUsers = $bccUsers->merge($advocates);
                        }
                    }

                    // Add clients from case's your_party_name
                    if (!empty($case->your_party_name)) {
                        $yourParties = json_decode($case->your_party_name, true);
                        if (is_array($yourParties)) {
                            foreach ($yourParties as $party) {
                                if (isset($party['clients']) && !empty($party['clients'])) {
                                    $client = User::find($party['clients']);
                                    if ($client && $client->type == 'client' && $client->id != $this->task->created_by) {
                                        $bccUsers->push($client);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Remove duplicates and filter out users without email
            $bccUsers = $bccUsers
                ->filter(function ($user) {
                    return $user && !empty($user->email);
                })
                ->unique('id');

            // Prepare BCC emails list
            $bccEmails = $bccUsers->pluck('email')->toArray();

            // Get case name if related
            $caseName = null;
            if (!empty($this->task->relate_to)) {
                $case = Cases::find($this->task->relate_to);
                if ($case) {
                    $caseName = $case->title;
                }
            }

            Log::info('Task notification recipients', [
                'task_id' => $this->task->id,
                'case_id' => $this->task->relate_to,
                'to_user' => ['id' => $creator->id, 'email' => $creator->email, 'name' => $creator->name],
                'bcc_recipients' => $bccUsers->map(function ($u) {
                    return ['id' => $u->id, 'email' => $u->email, 'name' => $u->name];
                })->toArray(),
                'bcc_count' => $bccUsers->count(),
            ]);

            // Send email with BCC
            try {
                $message = Mail::to($creator->email);

                // Add BCC recipients
                if (!empty($bccEmails)) {
                    $message->bcc($bccEmails);
                }

                $message->send(new TaskCreatedMail($this->task, $creator, $bccUsers, $caseName));

                Log::info("Task created notification sent", [
                    'task_id' => $this->task->id,
                    'case_id' => $this->task->relate_to,
                    'to_email' => $creator->email,
                    'bcc_count' => count($bccEmails),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send task notification email', [
                    'task_id' => $this->task->id,
                    'case_id' => $this->task->relate_to,
                    'to_email' => $creator->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            // Send push notifications to all users (creator + BCC users)
            $allUsers = collect([$creator])->merge($bccUsers)->unique('id');

            if ($allUsers->count() > 0) {
                try {
                    $pushService = new PushNotificationService();
                    $result = $pushService->sendTaskCreatedNotification(
                        $this->task,
                        $allUsers->all()
                    );

                    if ($result['success']) {
                        Log::info("Push notification sent for task created", [
                            'task_id' => $this->task->id,
                            'case_id' => $this->task->relate_to,
                            'users_count' => $allUsers->count(),
                        ]);
                    } else {
                        Log::warning("Push notification partially failed for task", [
                            'task_id' => $this->task->id,
                            'case_id' => $this->task->relate_to,
                            'result' => $result,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send task push notifications', [
                        'task_id' => $this->task->id,
                        'case_id' => $this->task->relate_to,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("Error processing task notification: " . $e->getMessage(), [
                'task_id' => $this->task->id ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
