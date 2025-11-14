<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ImportDataJob;
use Exception;
use League\Csv\Reader;

class ImportDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import {--filename=} {--batchsize=1000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from CSV file into the database using batch jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = $this->option('filename');
        $batchSize = (int) $this->option('batchsize');

        if (!$filename) {
            $this->error('Filename is required. Use --filename=yourfile.csv');
            return Command::FAILURE;
        }

        $csvPath = base_path("backup/{$filename}");

        if (!file_exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");
            return Command::FAILURE;
        }

        $this->info("Reading CSV file: {$csvPath}");
        $this->info("Batch size: {$batchSize}");

        try {
            // Read CSV file
            $csv = Reader::createFromPath($csvPath, 'r');
            $csv->setHeaderOffset(0);
            
            $headers = $csv->getHeader();
            $records = iterator_to_array($csv->getRecords());

            $totalRows = count($records);
            $this->info("Found {$totalRows} rows in CSV");

            // Check if is_insert column exists
            $hasIsInsertColumn = in_array('is_insert', $headers);
            
            if (!$hasIsInsertColumn) {
                $this->warn("'is_insert' column not found in CSV.");
            }

            // Filter rows where is_insert = 0 (or if column doesn't exist, process all)
            if ($hasIsInsertColumn) {
                $pendingRecords = array_filter($records, function($record) {
                    return isset($record['is_insert']) && $record['is_insert'] == '0';
                });
            } else {
                $pendingRecords = $records;
            }

            $pendingCount = count($pendingRecords);
            $this->info("Found {$pendingCount} rows to process");

            if ($pendingCount === 0) {
                $this->info("No pending rows to process");
                return Command::SUCCESS;
            }

            // Batch the data
            $batches = array_chunk($pendingRecords, $batchSize, true);
            $totalBatches = count($batches);

            $this->info("Dispatching {$totalBatches} batch jobs...");

            $progressBar = $this->output->createProgressBar($totalBatches);
            $progressBar->start();

            foreach ($batches as $batchIndex => $batch) {
                // Dispatch job for each batch
                ImportDataJob::dispatch($csvPath, $batch, $headers, $batchIndex + 1);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            $this->info("✅ Successfully dispatched {$totalBatches} batch jobs");
            $this->info("Jobs will process in the background. Monitor with: php artisan queue:work");

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
