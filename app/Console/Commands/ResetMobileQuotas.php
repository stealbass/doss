<?php

namespace App\Console\Commands;

use App\Models\MobileAppSubscription;
use Illuminate\Console\Command;

class ResetMobileQuotas extends Command
{
    protected $signature = 'mobile:reset-quotas';
    protected $description = 'Reset monthly quotas (search, AI analyses, PDF downloads) for active mobile subscriptions';

    public function handle(): int
    {
        $this->info('Resetting mobile quotas...');

        MobileAppSubscription::where('status', 'active')
            ->chunkById(500, function ($subs) {
                foreach ($subs as $sub) {
                    $sub->resetQuota();
                }
            });

        $this->info('Done.');
        return Command::SUCCESS;
    }
}
