<?php

namespace App\Exports;

use App\Models\AssignmentStatus;
use App\Models\SlsBusiness;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SlsBusinessExport
implements FromQuery, ShouldQueue, WithHeadings, WithMapping, WithChunkReading
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
     * @return \Illuminate\Support\Collection
     */
    public function query()
    {
        return SlsBusiness::query()->where('regency_id', '=', $this->regency);
    }

    public function headings(): array
    {
        return [
            'id',
            'Nama_Kabupaten',
            'Nama_Kecamatan',
            'Nama_Desa',
            'Nama_SLS',
            'Nama_Usaha',
            'Nama_Pemilik',
            'Sumber',
            'Status',
            'PCL',
        ];
    }

    public function map($business): array
    {
        return [
            $business->id,
            "[" . $business->regency->short_code . "] " .  $business->regency->name,
            "[" . $business->subdistrict->short_code . "] " .  $business->subdistrict->name,
            "[" . $business->village->short_code . "] " .  $business->village->name,
            "[" . $business->sls->short_code . "] " .  $business->sls->name,
            $business->name,
            $business->owner,
            $business->source,
            $business->status->name,
            $business->pcl != null ? $business->pcl->firstname : null,
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
