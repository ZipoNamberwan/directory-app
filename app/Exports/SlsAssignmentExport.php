<?php

namespace App\Exports;

use App\Models\ExportAssignmentStatus;
use App\Models\Sls;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class SlsAssignmentExport extends DefaultValueBinder
implements FromQuery, ShouldQueue, WithHeadings, WithMapping, WithCustomValueBinder
{
    use Exportable, Queueable;

    protected $regency;
    protected $uuid;

    public function __construct($regency, $uuid)
    {
        $this->regency = $regency;
        $this->uuid = $uuid;

        ExportAssignmentStatus::where('uuid', $uuid)->update([
            'status' => 'loading',
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function query()
    {
        return Sls::query()->where('long_code', 'like', $this->regency . '%');
    }

    public function headings(): array
    {
        return [
            'ID_SLS',
            'Nama_Kecamatan',
            'Nama_Desa',
            'Nama_SLS',
            'Email_PCL'
        ];
    }

    public function map($sls): array
    {
        return [
            strval($sls->id),
            "[" . $sls->village->subdistrict->short_code . "] " .  $sls->village->subdistrict->name,
            "[" . $sls->village->short_code . "] " .  $sls->village->name,
            $sls->name,
            ''
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
