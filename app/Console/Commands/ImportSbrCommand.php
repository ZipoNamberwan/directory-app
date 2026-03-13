<?php

namespace App\Console\Commands;

use App\Jobs\SbrJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportSbrCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-sbr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import SBR data from CSV files';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $folderPath = storage_path('../backup/split');

        $files = File::files($folderPath);

        foreach ($files as $file) {

            if ($file->getExtension() === 'csv') {

                SbrJob::dispatch($file->getRealPath());
            }
        }
    }
}
