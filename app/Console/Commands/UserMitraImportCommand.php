<?php

namespace App\Console\Commands;

use App\Jobs\UserMitraImportJob;
use App\Models\Regency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UserMitraImportCommand extends Command
{
    protected const DEFAULT_FOLDER = '../backup/users';
    protected const DEFAULT_CHUNK_SIZE = 500;

    protected $signature = 'app:import-mitra-users
                            {--chunk= : Number of rows per chunk}
                            {--batches= : Limit execution to the first N batches per file}';

    protected $description = 'Read Excel file(s) of mitra users (one file per regency) and dispatch import jobs with row batches';

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

        // Loaded once here so no job ever has to query the regencies table itself
        $regencyMap = Regency::pluck('id', 'long_code');

        $this->info('Chunk size: ' . $chunkSize);
        if ($batchLimit !== null) {
            $this->info('Batch limit: ' . $batchLimit . ' batch(es) per file');
        }
        $this->info('Found ' . $excelFiles->count() . ' Excel file(s)');
        $this->newLine();

        foreach ($excelFiles as $file) {
            $organizationId = '35' . pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $regencyId = $regencyMap[$organizationId] ?? null;

            $this->info("Processing: {$file->getFilename()} (organization: {$organizationId})");

            if (!$regencyId) {
                $this->warn("  No regency found with long_code {$organizationId}; users will be created without a regency.");
            }

            $this->readAndDispatch($file->getPathname(), $chunkSize, $batchLimit, $organizationId, $regencyId);
            $this->newLine();
        }

        return self::SUCCESS;
    }

    protected function readAndDispatch(string $path, int $chunkSize, ?int $batchLimit, string $organizationId, ?string $regencyId): void
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

        $flush = function () use (&$buffer, $header, &$dispatched, $organizationId, $regencyId) {
            if (empty($buffer)) {
                return;
            }

            // Dispatch job with the row batch directly — no file involved
            UserMitraImportJob::dispatch($header, $buffer, $organizationId, $regencyId);

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
