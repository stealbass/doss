<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Utility;
use App\Mail\EmployeeInviteMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmployeeCreatedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $employee;
    protected $plainPassword;

    /**
     * Create a new job instance.
     */
    public function __construct(User $employee, string $plainPassword)
    {
        $this->employee = $employee;
        $this->plainPassword = $plainPassword;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Load tenant SMTP settings
            Utility::getSMTPDetails($this->employee->created_by);

            // Ensure email exists
            if (empty($this->employee->email)) {
                Log::warning('Employee invite skipped: no email', [
                    'employee_id' => $this->employee->id,
                ]);
                return;
            }

            $loginUrl = url('/login');

            Mail::to($this->employee->email)
                ->send(new EmployeeInviteMail($this->employee, $this->plainPassword, $loginUrl));

            Log::info('Employee invite email sent', [
                'employee_id' => $this->employee->id,
                'email' => $this->employee->email,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send employee invite email', [
                'employee_id' => $this->employee->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
