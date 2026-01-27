<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ToDo;
use App\Jobs\SendTaskReminderNotification;
use Carbon\Carbon;

class SendTaskReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:tasks {--days=2 : Number of days before due date to send reminder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails for upcoming tasks';

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
        
        $this->info("Searching for tasks with due date on: " . $reminderDate->format('Y-m-d'));
        
        // Find all tasks due on this date that are not completed (status = 1 means active)
        $tasks = ToDo::whereDate('due_date', $reminderDate->format('Y-m-d'))
                     ->where('status', 1)
                     ->whereNotNull('due_date')
                     ->get();
        
        $count = 0;
        foreach ($tasks as $task) {
            SendTaskReminderNotification::dispatch($task);
            $count++;
            $this->info("Reminder scheduled for task ID: {$task->id}");
        }
        
        $this->info("Total task reminders sent: {$count}");
        
        return Command::SUCCESS;
    }
}
