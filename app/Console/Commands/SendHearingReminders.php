<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hearing;
use App\Jobs\SendHearingReminderNotification;
use Carbon\Carbon;

class SendHearingReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:hearings {--days=2 : Number of days before hearing to send reminder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails for upcoming hearings';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $daysBeforeInput = $this->option('days');
        $daysBeforeInputValue = is_numeric($daysBeforeInput) ? (int)$daysBeforeInput : 2;
        
        // Get today's date
        $today = Carbon::today();
        
        // Calculate the reminder date (2 days from now by default)
        $reminderDate = $today->copy()->addDays($daysBeforeInputValue);
        
        $this->info("Searching for hearings on: " . $reminderDate->format('Y-m-d'));
        
        // Find all hearings scheduled for this date
        $hearings = Hearing::whereDate('date', $reminderDate->format('Y-m-d'))
                          ->get();
        
        $count = 0;
        foreach ($hearings as $hearing) {
            SendHearingReminderNotification::dispatch($hearing);
            $count++;
            $this->info("Reminder scheduled for hearing ID: {$hearing->id}");
        }
        
        $this->info("Total reminders sent: {$count}");
        
        return Command::SUCCESS;
    }
}
