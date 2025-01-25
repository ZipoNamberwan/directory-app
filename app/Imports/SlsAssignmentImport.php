<?php

namespace App\Imports;

use App\Models\AssignmentStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SlsAssignmentImport implements WithMultipleSheets, ShouldQueue, WithChunkReading
{
    use Importable, Queueable;

    protected $uuid;
    protected $regency;

    public function __construct($regency, $uuid)
    {
        $this->uuid = $uuid;
        $this->regency = $regency;

        AssignmentStatus::find($uuid)->update([
            'status' => 'loading',
        ]);
    }

    public function sheets(): array
    {
        return [
            0 => new SlsAssignmentImportSheet($this->regency, $this->uuid),
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
