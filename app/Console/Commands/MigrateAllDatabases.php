<?php

namespace App\Console\Commands;

use App\Helpers\DatabaseSelector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MigrateAllDatabases extends Command
{
    protected $signature = 'migrate:all {--fresh} {--rollback} {--seed}';
    protected $description = 'Run migrations on all databases with options for fresh, rollback, and seeding.';

    public function handle()
    {
        $this->info("Migrating all database...");

        $connections = DatabaseSelector::getListConnections();

        foreach ($connections as $connection) {
            $this->info("🔄 Processing $connection");

            if ($this->option('rollback')) {
                $this->rollbackDatabase($connection);
            } elseif ($this->option('fresh')) {
                $this->freshMigrateDatabase($connection);
            } else {
                $this->migrateDatabase($connection);
            }
        }

        if ($this->option('seed')) {
            $this->seedDatabase();
        }

        $this->info("✅ Migration process completed for all databases!");
    }

    private function migrateDatabase($connection)
    {
        Artisan::call('migrate', ['--database' => $connection,  '--force' => true,]);
        $this->info("Migrated: $connection");
    }

    private function freshMigrateDatabase($connection)
    {
        Artisan::call('migrate:fresh', ['--database' => $connection]);
        $this->info("Fresh migrated: $connection");
    }

    private function rollbackDatabase($connection)
    {
        Artisan::call('migrate:rollback', ['--database' => $connection,  '--force' => true,]);
        $this->info("Rolled back: $connection");
    }

    private function seedDatabase()
    {
        Artisan::call('db:seed');
        $this->info("Seeded: all databases");
    }
}
