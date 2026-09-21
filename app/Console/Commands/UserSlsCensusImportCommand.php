<?php

namespace App\Console\Commands;

use App\Jobs\UserSlsCensusImportJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UserSlsCensusImportCommand extends Command
{
    protected const DEFAULT_FOLDER = '../backup/allocation';
    protected const DEFAULT_CHUNK_SIZE = 1000;

    protected $signature = 'app:import-user-sls-census
                            {--chunk= : Number of rows per chunk}
                            {--batches= : Limit execution to the first N batches per file}';

    protected $description = 'Read Excel file(s) of user SLS census allocation and dispatch import jobs with row batches';

    public function handle(): int
    {
        $chunkSize = (int) ($this->option('chunk') ?? self::DEFAULT_CHUNK_SIZE);
        $batchLimit = $this->option('batches') !== null ? (int) $this->option('batches') : null;

        $folderPath = storage_path(self::DEFAULT_FOLDER);

        if (!File::isDirectory($folderPath)) {
            $this->error("Folder not found: {$folderPath}");
            return self::FAILURE;
        }

        $files = File::files($folderPath);

        $excelFiles = collect($files)->filter(
            fn ($file) => in_array(strtolower($file->getExtension()), ['xlsx', 'xls'])
        );

        if ($excelFiles->isEmpty()) {
            $this->error("No Excel files found in: {$folderPath}");
            return self::FAILURE;
        }

        // Reset so this run's failed.csv doesn't mix in stale rows from a previous run
        File::delete($folderPath . '/failed.csv');

        $this->info('Chunk size: ' . $chunkSize);
        if ($batchLimit !== null) {
            $this->info('Batch limit: ' . $batchLimit . ' batch(es) per file');
        }
        $this->info('Found ' . $excelFiles->count() . ' Excel file(s)');
        $this->newLine();

        foreach ($excelFiles as $file) {
            $this->info("Processing: {$file->getFilename()}");
            $this->readAndDispatch($file->getPathname(), $chunkSize, $batchLimit);
            $this->newLine();
        }

        return self::SUCCESS;
    }

    protected function readAndDispatch(string $path, int $chunkSize, ?int $batchLimit): void
    {
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, false, false);

        if (empty($rows)) {
            $this->warn("Excel file appears empty, skipping: {$path}");
            return;
        }

        $header = array_shift($rows);

        $rowCount = 0;
        $dispatched = 0;
        $buffer = [];
        $limitReached = false;

        $flush = function () use (&$buffer, $header, &$dispatched) {
            if (empty($buffer)) {
                return;
            }

            // Dispatch job with the row batch directly — no file involved
            UserSlsCensusImportJob::dispatch($header, $buffer);

            $dispatched++;
            $buffer = [];
        };

        foreach ($rows as $row) {
            if ($batchLimit !== null && $dispatched >= $batchLimit) {
                $limitReached = true;
                break;
            }

            $buffer[] = $row;
            $rowCount++;

            if (count($buffer) >= $chunkSize) {
                $flush();
            }
        }

        // Only flush the remaining partial batch if we weren't cut off by the limit
        if (!$limitReached) {
            $flush();
        }

        $suffix = $limitReached ? " (stopped early, batch limit reached)" : '';
        $this->line("  → {$rowCount} rows read, {$dispatched} job(s) dispatched.{$suffix}");
    }
}
