<?php

namespace App\Console\Commands;

use App\Models\LudoBattle;
use Illuminate\Console\Command;

class ProcessExpiredMatchDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ludo:process-expired-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically detect expired match-result submission deadlines and transition them to administrative review idempotently.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Scanning for matches with expired result submission deadlines...');

        $processed = LudoBattle::processExpiredDeadlines();

        if ($processed > 0) {
            $this->info("Successfully processed {$processed} expired match(es) and placed under administrative review.");
        } else {
            $this->comment('No active matches with expired submission deadlines found.');
        }

        return Command::SUCCESS;
    }
}
