<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Plan;
use App\Models\Utility;
use App\Mail\SubscriptionExpiringNotification;
use App\Mail\AdminSubscriptionNotification;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckExpiringSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-expiring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expiring subscriptions and send notification emails';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking for expiring subscriptions...');
        
        $now = Carbon::now();
        $warningDays = [7, 3, 1]; // Send alerts 7, 3, and 1 day before expiration
        
        foreach ($warningDays as $days) {
            $targetDate = $now->copy()->addDays($days)->format('Y-m-d');
            
            // Get users whose subscription expires on target date
            $expiringUsers = User::where('type', 'company')
                ->whereDate('plan_expire_date', $targetDate)
                ->get();
            
            foreach ($expiringUsers as $user) {
                try {
                    // Configure SMTP
                    Utility::getSMTPDetails(1); // Admin SMTP settings
                    
                    // Get plan details
                    $plan = Plan::find($user->plan);
                    
                    if (!$plan) {
                        continue;
                    }
                    
                    $planPrice = \App\Models\Utility::getValByName('currency_symbol') . 
                                number_format($plan->price, 2);
                    $planDuration = $plan->duration === 'month' ? 'Mensuel' : 'Annuel';
                    
                    // Prepare email data for user
                    $emailData = [
                        'userName' => $user->name,
                        'planName' => $plan->name,
                        'planPrice' => $planPrice,
                        'planDuration' => $planDuration,
                        'expirationDate' => $user->plan_expire_date,
                        'daysLeft' => $days,
                        'renewUrl' => route('plans.index'),
                    ];
                    
                    // Send email to user
                    if ($user->email) {
                        Mail::to($user->email)->send(
                            new SubscriptionExpiringNotification($user, $emailData)
                        );
                        
                        $this->info("Email sent to {$user->email} ({$days} days left)");
                        Log::info('Expiring subscription email sent', [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'days_left' => $days
                        ]);
                    }
                    
                    // Send email to admin
                    $adminEmail = \App\Models\Utility::getValByName('mail_from_address');
                    if ($adminEmail) {
                        $adminEmailData = [
                            'type' => 'expiring',
                            'userName' => $user->name,
                            'userEmail' => $user->email,
                            'planName' => $plan->name,
                            'planPrice' => $planPrice,
                            'expirationDate' => $user->plan_expire_date,
                            'daysLeft' => $days,
                            'adminUrl' => route('users.index'),
                        ];
                        
                        Mail::to($adminEmail)->send(
                            new AdminSubscriptionNotification($user, $plan, $adminEmailData, 'expiring')
                        );
                        
                        $this->info("Admin notification sent for {$user->name}");
                    }
                    
                } catch (\Exception $e) {
                    $this->error("Error sending email to {$user->email}: " . $e->getMessage());
                    Log::error('Error sending expiring subscription email', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        $this->info('Expiring subscriptions check completed!');
        return Command::SUCCESS;
    }
}
