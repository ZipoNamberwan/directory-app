<?php

namespace App\Console\Commands;

use App\Jobs\AgricultureJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportAgricultureCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-agriculture';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import agriculture data from CSV files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $folderPath = storage_path('../backup/agriculture');

        $files = File::files($folderPath);

        foreach ($files as $file) {

            if ($file->getExtension() === 'csv') {

                AgricultureJob::dispatch($file->getRealPath());
            }
        }
    }
}
