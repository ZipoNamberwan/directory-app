<?php

namespace App\Imports;

use App\Jobs\ImportAdditionalJob;
use App\Models\AssignmentStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;

class AdditionalImport implements ToCollection, WithChunkReading, WithStartRow, ShouldQueue
{
    use Importable, Queueable;

    protected $uuid;
    protected $regency;
    protected $userId;

    public function __construct($regency, $uuid, $userId)
    {
        $this->uuid = $uuid;
        $this->regency = $regency;
        $this->userId = $userId;

        AssignmentStatus::find($uuid)->update([
            'status' => 'loading',
        ]);
    }
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        ImportAdditionalJob::dispatch($rows, $this->regency, $this->uuid, $this->userId);
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
