<?php

namespace App\Console\Commands;

use App\Models\Monitoring\MonitorResult;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('monitors:prune-results {--days=14}')]
#[Description('Delete old monitor results')]
class PruneMonitorResults extends Command
{
    public function handle(): int
    {
        $days = (int)$this->option('days');

        $deleted = MonitorResult::where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$deleted} old monitor results.");

        return self::SUCCESS;
    }
}
