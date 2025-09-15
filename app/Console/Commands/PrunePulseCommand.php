<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrunePulseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prune-pulse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune pulse command';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        DB::table('pulse_entries')->truncate();
        DB::table('pulse_values')->truncate();
        DB::table('pulse_aggregates')->truncate();
    }
}
