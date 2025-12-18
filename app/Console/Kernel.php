<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Send hearing reminders daily at 9:00 AM (2 days before the hearing)
        $schedule->command('reminders:hearings --days=2')
                 ->dailyAt('09:00')
                 ->name('hearing-reminders-2-days')
                 ->onSuccess(function () {
                     \Log::info('Hearing reminders sent successfully');
                 })
                 ->onFailure(function () {
                     \Log::error('Hearing reminders failed');
                 });

        // Send task reminders daily at 9:00 AM (2 days before due date)
        $schedule->command('reminders:tasks --days=2')
                 ->dailyAt('09:00')
                 ->name('task-reminders-2-days')
                 ->onSuccess(function () {
                     \Log::info('Task reminders sent successfully');
                 })
                 ->onFailure(function () {
                     \Log::error('Task reminders failed');
                 });

        // Optional: Send additional reminder 1 day before
        $schedule->command('reminders:hearings --days=1')
                 ->dailyAt('18:00')
                 ->name('hearing-reminders-1-day');

        $schedule->command('reminders:tasks --days=1')
                 ->dailyAt('18:00')
                 ->name('task-reminders-1-day');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
