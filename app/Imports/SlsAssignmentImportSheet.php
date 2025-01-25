<?php

namespace App\Imports;

use App\Jobs\ImportAssignmentJob;
use App\Models\AssignmentStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;

class SlsAssignmentImportSheet implements ToCollection, WithChunkReading, WithStartRow, ShouldQueue
{
    use Importable, Queueable;

    protected $uuid;
    protected $regency;

    public function __construct($regency, $uuid)
    {
        $this->uuid = $uuid;
        $this->regency = $regency;
    }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        ImportAssignmentJob::dispatch($rows, $this->regency, $this->uuid);
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function startRow(): int
    {
        return 2;
    }
}
