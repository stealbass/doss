<?php

namespace App\Jobs;

use App\Models\Bill;
use App\Models\User;
use App\Mail\BillCreatedMail;
use App\Models\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendBillCreatedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bill;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Bill $bill)
    {
        $this->bill = $bill;
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
            Utility::getSMTPDetails($this->bill->created_by);

            Log::info('SendBillCreatedNotification job started', [
                'bill_id' => $this->bill->id,
                'bill_from' => $this->bill->bill_from,
                'bill_to' => $this->bill->bill_to,
                'bill_number' => $this->bill->bill_number,
                'created_by' => $this->bill->created_by,
            ]);

            // Primary recipient: the user who created the bill (Admin only)
            $admin = User::find($this->bill->created_by);
            if (!$admin || empty($admin->email)) {
                Log::error('Bill creator (admin) not found or has no email', [
                    'bill_id' => $this->bill->id,
                    'created_by' => $this->bill->created_by
                ]);
                return;
            }

            // Get bill recipient info
            $billToUser = User::find($this->bill->bill_to);
            $billToName = $billToUser ? $billToUser->name : 'Client/User';
            
            // Get bill from info (advocate or company)
            $billFromName = $this->bill->bill_from == 'advocate' 
                ? (User::find($this->bill->advocate)->name ?? 'Advocate')
                : ($this->bill->advocate ?? 'Company');

            Log::info('Bill notification - Admin only', [
                'bill_id' => $this->bill->id,
                'admin_email' => $admin->email,
                'admin_name' => $admin->name,
                'bill_number' => $this->bill->bill_number,
                'bill_from' => $billFromName,
                'bill_to' => $billToName,
            ]);

            // Send email to admin only (TO recipient)
            try {
                Mail::to($admin->email)->send(new BillCreatedMail(
                    $this->bill,
                    $admin,
                    $billToName,
                    $billFromName
                ));

                Log::info("Bill created notification sent to admin", [
                    'bill_id' => $this->bill->id,
                    'admin_email' => $admin->email,
                    'bill_number' => $this->bill->bill_number,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send bill notification email to admin', [
                    'bill_id' => $this->bill->id,
                    'admin_email' => $admin->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Error processing bill notification: " . $e->getMessage(), [
                'bill_id' => $this->bill->id ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
