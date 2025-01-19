<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MigrateWithQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:fresh-seed-queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrate:fresh --seed and queue:work automatically';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Run migrate:fresh --seed
        $this->info('Running migrations and seeding the database...');
        Artisan::call('migrate:fresh', ['--seed' => true]);
        $this->info(Artisan::output());

        // Run queue:work
        $this->info('Starting queue worker...');
        Artisan::call('queue:work', [
            '--stop-when-empty' => true, // Optional flag to stop when the queue is empty
        ]);
        $this->info(Artisan::output());

        $this->info('Migration, seeding, and queue worker completed successfully.');
    }
}
