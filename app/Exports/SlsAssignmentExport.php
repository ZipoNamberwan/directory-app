<?php

namespace App\Exports;

use App\Models\AssignmentStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SlsAssignmentExport implements WithMultipleSheets, ShouldQueue
{
    use Exportable, Queueable;

    protected $regency;
    protected $uuid;

    public function __construct($regency, $uuid)
    {
        $this->regency = $regency;
        $this->uuid = $uuid;

        AssignmentStatus::find($uuid)->update([
            'status' => 'loading',
        ]);
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];
        $sheets[] = new SlsAssignmentExportSheet($this->regency);
        $sheets[] = new UserAssignmentExportSheet($this->regency);

        return $sheets;
    }
}
