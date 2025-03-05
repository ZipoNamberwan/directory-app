<?php

namespace App\Exports;

use App\Helpers\DatabaseSelector;
use App\Models\AssignmentStatus;
use App\Models\NonSlsBusiness;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class NonSlsBusinessExport
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
        return NonSlsBusiness::on(DatabaseSelector::getConnection($this->regency))->query()->where('regency_id', '=', $this->regency);
    }

    public function headings(): array
    {
        return [
            'id',
            'level',
            'Nama_Kabupaten',
            'Nama_Kecamatan',
            'Nama_Desa',
            'Nama_SLS',
            'Nama_Usaha',
            'Nama_Pemilik',
            'Kategori',
            'KBLI',
            'Sumber',
            'Status',
            'PCL',
        ];
    }

    public function map($business): array
    {
        return [
            $business->id,
            $business->level,
            "[" . $business->regency->short_code . "] " .  $business->regency->name,
            $business->subdistrict != null ? ("[" . $business->subdistrict->short_code . "] " .  $business->subdistrict->name) : null,
            $business->village != null ? ("[" . $business->village->short_code . "] " .  $business->village->name) : null,
            $business->sls != null ? ("[" . $business->sls->short_code . "] " .  $business->sls->name) : null,
            $business->name,
            $business->owner,
            $business->category,
            $business->kbli,
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
