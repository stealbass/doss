<?php

namespace App\Jobs;

use App\Models\Cases;
use App\Models\User;
use App\Models\Court;
use App\Models\Utility;
use App\Mail\NewCaseNotification;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendCaseCreatedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $case;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Cases $case)
    {
        $this->case = $case;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info("Starting SendCaseCreatedNotification Job", [
                'case_id' => $this->case->id,
                'case_title' => $this->case->title,
                'created_by' => $this->case->created_by
            ]);
            
            // Load SMTP settings from database (CRITICAL!)
            Utility::getSMTPDetails($this->case->created_by);
            
            // 1. Get case creator/admin (primary recipient)
            $creator = User::find($this->case->created_by);
            if (!$creator || empty($creator->email)) {
                Log::warning("Case creator not found or has no email", [
                    'case_id' => $this->case->id,
                    'created_by' => $this->case->created_by
                ]);
                return;
            }

            // 2. Collect BCC recipients (advocates and clients)
            $bccUsers = collect();

            // Add all advocates assigned to the case
            if (!empty($this->case->advocates)) {
                $advocateIds = explode(',', $this->case->advocates);
                $advocates = User::whereIn('id', $advocateIds)->where('type', 'advocate')->get();
                $bccUsers = $bccUsers->merge($advocates);
            }

            // Add clients from your_party_name
            if (!empty($this->case->your_party_name)) {
                $your_parties = json_decode($this->case->your_party_name, true);
                if (is_array($your_parties)) {
                    Log::info("Processing clients from your_party_name", [
                        'case_id' => $this->case->id,
                        'party_count' => count($your_parties)
                    ]);
                    
                    foreach ($your_parties as $party) {
                        if (isset($party['clients']) && !empty($party['clients'])) {
                            $client = User::find($party['clients']);
                            if ($client && $client->type == 'client') {
                                $bccUsers->push($client);
                                Log::debug("Added client to BCC recipients", [
                                    'case_id' => $this->case->id,
                                    'client_id' => $party['clients'],
                                    'client_name' => $client->name,
                                    'client_email' => $client->email
                                ]);
                            } else {
                                Log::warning("Client not found or not of type 'client'", [
                                    'case_id' => $this->case->id,
                                    'client_id' => $party['clients']
                                ]);
                            }
                        }
                    }
                } else {
                    Log::warning("your_party_name is not a valid array after json_decode", [
                        'case_id' => $this->case->id
                    ]);
                }
            } else {
                Log::info("No your_party_name data found", [
                    'case_id' => $this->case->id
                ]);
            }

            // Remove duplicates
            $bccUsers = $bccUsers->unique('id')->filter(function($user) {
                return !empty($user->email);
            });

            // Prepare email data
            $clients = [];
            if (!empty($this->case->your_party_name)) {
                $your_parties = json_decode($this->case->your_party_name, true);
                if (is_array($your_parties)) {
                    foreach ($your_parties as $party) {
                        if (isset($party['name']) && !empty($party['name'])) {
                            $clients[] = [
                                'name' => $party['name'],
                                'client_id' => $party['clients'] ?? null
                            ];
                        }
                    }
                }
            }
            
            // Get court name
            $courtName = '';
            if ($this->case->court) {
                $court = Court::find($this->case->court);
                if ($court) {
                    $courtName = $court->name;
                }
            }
            
            // Case URL
            $caseUrl = route('cases.show', $this->case->id);

            // Prepare email data for admin recipient
            $emailData = [
                'case' => $this->case,
                'recipientName' => $creator->name,
                'clients' => $clients,
                'courtName' => $courtName,
                'caseUrl' => $caseUrl,
            ];
            
            // Build the email
            $message = Mail::to($creator->email);
            
            // Add BCC recipients
            if ($bccUsers->isNotEmpty()) {
                $bccEmails = $bccUsers->pluck('email')->toArray();
                $message->bcc($bccEmails);
                
                $bccDetails = $bccUsers->map(function($user) {
                    return $user->name . ' (' . $user->type . ') - ' . $user->email;
                })->toArray();
                
                Log::info("Case created notification sent with BCC", [
                    'case_id' => $this->case->id,
                    'to' => $creator->email,
                    'to_name' => $creator->name,
                    'bcc_count' => count($bccEmails),
                    'bcc_recipients' => implode(' | ', $bccDetails)
                ]);
            } else {
                Log::info("Case created notification sent (no BCC recipients)", [
                    'case_id' => $this->case->id,
                    'to' => $creator->email,
                    'to_name' => $creator->name
                ]);
            }
            
            // Send the email
            $message->send(new NewCaseNotification($this->case, $emailData));

            // Send push notifications to all users (admin + BCC recipients)
            $allUsers = collect([$creator]);
            if ($bccUsers->isNotEmpty()) {
                $allUsers = $allUsers->merge($bccUsers);
            }
            
            if ($allUsers->count() > 0) {
                try {
                    $pushService = new PushNotificationService();
                    $result = $pushService->sendCaseCreatedNotification(
                        $this->case,
                        $allUsers->toArray()
                    );
                    
                    if ($result['success']) {
                        Log::info("Push notification sent for case created", [
                            'case_id' => $this->case->id,
                            'users_count' => $allUsers->count(),
                        ]);
                    }
                } catch (\Exception $pushError) {
                    Log::warning("Failed to send push notification for case", [
                        'case_id' => $this->case->id,
                        'error' => $pushError->getMessage()
                    ]);
                    // Don't fail the whole job if push notification fails
                }
            }

        } catch (\Exception $e) {
            Log::error("Error sending case created notification: " . $e->getMessage(), [
                'case_id' => $this->case->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
