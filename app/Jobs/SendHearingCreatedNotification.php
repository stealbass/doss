<?php

namespace App\Jobs;

use App\Models\Hearing;
use App\Models\Cases;
use App\Models\User;
use App\Mail\HearingCreatedMail;
use App\Services\PushNotificationService;
use App\Models\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendHearingCreatedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $hearing;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Hearing $hearing)
    {
        $this->hearing = $hearing;
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
            Utility::getSMTPDetails($this->hearing->created_by);

            Log::info('SendHearingCreatedNotification job started', [
                'hearing_id' => $this->hearing->id,
                'assigned_to' => $this->hearing->assigned_to,
                'created_by' => $this->hearing->created_by,
            ]);
            
            $case = Cases::find($this->hearing->case_id);
            
            if (!$case) {
                Log::warning("Case not found for hearing ID: {$this->hearing->id}");
                return;
            }

            // Primary recipient: the user who created the hearing
            $toUser = User::find($this->hearing->created_by);
            if (!$toUser || empty($toUser->email)) {
                Log::error('Hearing creator not found or has no email', ['hearing_id' => $this->hearing->id, 'created_by' => $this->hearing->created_by]);
                return;
            }

            // Collect BCC recipients
            $bccUsers = collect();
            
            // Add assigned users from hearing->assigned_to
            if (!empty($this->hearing->assigned_to)) {
                $assignedIds = $this->hearing->assigned_to;
                
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
                    $assignedUsers = User::whereIn('id', $assignedIds)->where('id', '!=', $this->hearing->created_by)->get();
                    $bccUsers = $bccUsers->merge($assignedUsers);
                }
            }

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
                    $advocates = User::whereIn('id', $advocateIds)->where('id', '!=', $this->hearing->created_by)->get();
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
                            if ($client && $client->type == 'client' && $client->id != $this->hearing->created_by) {
                                $bccUsers->push($client);
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

            Log::info('Hearing notification recipients', [
                'hearing_id' => $this->hearing->id,
                'case_id' => $case->id,
                'to_user' => ['id' => $toUser->id, 'email' => $toUser->email, 'name' => $toUser->name],
                'bcc_recipients' => $bccUsers->map(function ($u) {
                    return ['id' => $u->id, 'email' => $u->email, 'name' => $u->name];
                })->toArray(),
                'bcc_count' => $bccUsers->count(),
            ]);

            // Send email with BCC
            try {
                $message = Mail::to($toUser->email);
                
                // Add BCC recipients
                if (!empty($bccEmails)) {
                    $message->bcc($bccEmails);
                }
                
                $message->send(new HearingCreatedMail($this->hearing, $case, $toUser, $bccUsers));
                
                Log::info("Hearing created notification sent", [
                    'hearing_id' => $this->hearing->id,
                    'case_id' => $case->id,
                    'to_email' => $toUser->email,
                    'bcc_count' => count($bccEmails),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send hearing notification email', [
                    'hearing_id' => $this->hearing->id,
                    'case_id' => $case->id,
                    'to_email' => $toUser->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            // Send push notifications to all users (creator + BCC users)
            $allUsers = collect([$toUser])->merge($bccUsers)->unique('id');

            if ($allUsers->count() > 0) {
                try {
                    $pushService = new PushNotificationService();
                    $result = $pushService->sendHearingCreatedNotification(
                        $this->hearing,
                        $case,
                        $allUsers->all()
                    );
                    
                    if ($result['success']) {
                        Log::info("Push notification sent for hearing created", [
                            'hearing_id' => $this->hearing->id,
                            'case_id' => $case->id,
                            'users_count' => $allUsers->count(),
                        ]);
                    } else {
                        Log::warning("Push notification partially failed for hearing", [
                            'hearing_id' => $this->hearing->id,
                            'case_id' => $case->id,
                            'result' => $result,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send hearing push notifications', [
                        'hearing_id' => $this->hearing->id,
                        'case_id' => $case->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("Error processing hearing notification: " . $e->getMessage(), [
                'hearing_id' => $this->hearing->id ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
