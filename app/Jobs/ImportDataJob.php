<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\Csv\Reader;
use League\Csv\Writer;

class ImportDataJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 0;

    protected $csvPath;
    protected $batch;
    protected $headers;
    protected $batchIndex;

    /**
     * Create a new job instance.
     */
    public function __construct($csvPath, $batch, $headers, $batchIndex)
    {
        $this->csvPath = $csvPath;
        $this->batch = $batch;
        $this->headers = $headers;
        $this->batchIndex = $batchIndex;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $insertedCount = 0;
        $failedCount = 0;

        // Required columns for supplement_business
        $requiredColumns = [
            'name', 'owner', 'status', 'address', 'description',
            'sector', 'note', 'latitude', 'longitude',
            'organization_id', 'user_id', 'project_id'
        ];

        // Read the entire CSV to update it
        $csvContent = file_get_contents($this->csvPath);
        $allRows = array_map('str_getcsv', explode("\n", trim($csvContent)));
        
        $isInsertIndex = array_search('is_insert', $this->headers);

        // Process each row in the batch
        foreach ($this->batch as $originalIndex => $row) {
            try {
                // Prepare data for insertion
                $data = [];
                
                foreach ($requiredColumns as $column) {
                    $columnIndex = array_search($column, $this->headers);
                    if ($columnIndex !== false && isset($row[$column])) {
                        $value = trim($row[$column]);
                        // Clean the value: remove newlines and extra spaces
                        $value = $this->cleanValue($value);
                        $data[$column] = ($value === '' || $value === null) ? null : $value;
                    } else {
                        $data[$column] = null;
                    }
                }

                // Validate required fields
                if (empty($data['user_id'])) {
                    $failedCount++;
                    continue;
                }

                if (empty($data['organization_id'])) {
                    $failedCount++;
                    continue;
                }

                if (empty($data['project_id'])) {
                    $failedCount++;
                    continue;
                }

                // Add UUID and timestamps
                $data['id'] = (string) Str::uuid();
                $data['created_at'] = now();
                $data['updated_at'] = now();

                // Insert into database
                DB::table('supplement_business')->insert($data);

                // Update CSV: mark as inserted (is_insert = 1)
                if ($isInsertIndex !== false) {
                    // Find the actual row index in the full CSV
                    // originalIndex is the key from the filtered array
                    $realRowIndex = $this->findRowIndexInCsv($allRows, $row, $this->headers);
                    
                    if ($realRowIndex !== false && isset($allRows[$realRowIndex][$isInsertIndex])) {
                        $allRows[$realRowIndex][$isInsertIndex] = '1';
                    }
                }

                $insertedCount++;

            } catch (\Exception $e) {
                $failedCount++;
                Log::error("ImportDataJob: Failed to insert row in batch {$this->batchIndex}: " . $e->getMessage());
            }
        }

        // Save updated CSV back to file
        if ($isInsertIndex !== false) {
            $this->saveCsv($allRows);
        }

        Log::info("ImportDataJob: Batch {$this->batchIndex} completed. Inserted: {$insertedCount}, Failed: {$failedCount}");
    }

    /**
     * Find the row index in the full CSV array
     */
    protected function findRowIndexInCsv($allRows, $targetRow, $headers)
    {
        // Skip header row (index 0)
        for ($i = 1; $i < count($allRows); $i++) {
            // Compare key fields to identify the row
            $match = true;
            foreach ($headers as $headerIndex => $headerName) {
                if (isset($targetRow[$headerName]) && isset($allRows[$i][$headerIndex])) {
                    if ($targetRow[$headerName] !== $allRows[$i][$headerIndex]) {
                        $match = false;
                        break;
                    }
                }
            }
            
            if ($match) {
                return $i;
            }
        }
        
        return false;
    }

    /**
     * Save updated CSV file
     */
    protected function saveCsv($rows)
    {
        try {
            $updatedCsv = collect($rows)
                ->map(fn($r) => implode(',', array_map([$this, 'escapeCsvValue'], $r)))
                ->implode("\n");

            file_put_contents($this->csvPath, $updatedCsv);
        } catch (\Exception $e) {
            Log::error("ImportDataJob: Failed to save CSV: " . $e->getMessage());
        }
    }

    /**
     * Escape CSV value (handle commas, quotes, etc.)
     */
    private function escapeCsvValue($value)
    {
        $value = (string) $value;
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            $value = '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }

    /**
     * Clean value by removing newlines and extra spaces
     */
    private function cleanValue($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        // Remove all types of newlines and carriage returns
        $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);
        
        // Replace multiple spaces with single space
        $value = preg_replace('/\s+/', ' ', $value);
        
        // Trim leading and trailing spaces
        $value = trim($value);
        
        return $value;
    }
}
