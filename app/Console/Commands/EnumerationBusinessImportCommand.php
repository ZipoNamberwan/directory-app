<?php

namespace App\Console\Commands;

use App\Jobs\EnumerationBusinessJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class EnumerationBusinessImportCommand extends Command
{
    protected const DEFAULT_FOLDER = '../backup/enumeration';
    protected const DEFAULT_CHUNK_SIZE = 1000;

    protected $signature = 'app:import-enumeration 
                            {--chunk= : Number of rows per chunk}
                            {--batches= : Limit execution to the first N batches per file}';

    protected $description = 'Read large CSV file(s) and dispatch import jobs with row batches';

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

        $csvFiles = collect($files)->filter(
            fn ($file) => strtolower($file->getExtension()) === 'csv'
        );

        if ($csvFiles->isEmpty()) {
            $this->error("No CSV files found in: {$folderPath}");
            return self::FAILURE;
        }

        $this->info('Chunk size: ' . $chunkSize);
        if ($batchLimit !== null) {
            $this->info('Batch limit: ' . $batchLimit . ' batch(es) per file');
        }
        $this->info('Found ' . $csvFiles->count() . ' CSV file(s)');
        $this->newLine();

        foreach ($csvFiles as $file) {
            $this->info("Processing: {$file->getFilename()}");
            $this->readAndDispatch($file->getPathname(), $chunkSize, $batchLimit);
            $this->newLine();
        }

        return self::SUCCESS;
    }

    protected function readAndDispatch(string $path, int $chunkSize, ?int $batchLimit): void
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->error("Unable to open file: {$path}");
            return;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            $this->warn("CSV appears empty, skipping: {$path}");
            fclose($handle);
            return;
        }

        $rowCount = 0;
        $dispatched = 0;
        $buffer = [];
        $limitReached = false;

        $flush = function () use (&$buffer, $header, &$dispatched) {
            if (empty($buffer)) {
                return;
            }

            // Dispatch job with the row batch directly — no file involved
            EnumerationBusinessJob::dispatch($header, $buffer);

            $dispatched++;
            $buffer = [];
        };

        while (($row = fgetcsv($handle)) !== false) {
            if ($batchLimit !== null && $dispatched >= $batchLimit) {
                $limitReached = true;
                break;
            }

            if (count($row) !== count($header)) {
                continue; // skip malformed row
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

        fclose($handle);

        $suffix = $limitReached ? " (stopped early, batch limit reached)" : '';
        $this->line("  → {$rowCount} rows read, {$dispatched} job(s) dispatched.{$suffix}");
    }
}