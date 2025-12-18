<?php

namespace App\Jobs;

use App\Models\Hearing;
use App\Models\Cases;
use App\Models\User;
use App\Mail\HearingCreatedMail;
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
            $case = Cases::find($this->hearing->case_id);
            
            if (!$case) {
                Log::warning("Case not found for hearing ID: {$this->hearing->id}");
                return;
            }

            // Get all users involved in the case
            $usersToNotify = collect();
            
            // Add case creator
            $creator = User::find($case->created_by);
            if ($creator) {
                $usersToNotify->push($creator);
            }

            // Add advocates
            if (!empty($case->advocates)) {
                $advocateIds = is_array($case->advocates) ? $case->advocates : json_decode($case->advocates, true);
                if ($advocateIds) {
                    $advocates = User::whereIn('id', $advocateIds)->get();
                    $usersToNotify = $usersToNotify->merge($advocates);
                }
            }

            // Add company owner
            $company = User::find($case->created_by);
            if ($company && $company->creatorId()) {
                $owner = User::find($company->creatorId());
                if ($owner) {
                    $usersToNotify->push($owner);
                }
            }

            // Remove duplicates
            $usersToNotify = $usersToNotify->unique('id');

            // Send email to each user
            foreach ($usersToNotify as $user) {
                if (!empty($user->email)) {
                    Mail::to($user->email)->send(new HearingCreatedMail($this->hearing, $case, $user));
                    Log::info("Hearing created notification sent to: {$user->email}");
                }
            }

        } catch (\Exception $e) {
            Log::error("Error sending hearing created notification: " . $e->getMessage());
        }
    }
}
