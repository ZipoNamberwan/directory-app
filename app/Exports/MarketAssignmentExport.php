<?php

namespace App\Exports;

use App\Models\Market;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class MarketAssignmentExportSheet implements FromCollection, WithHeadings, ShouldQueue,  WithTitle
{
    use Exportable, Queueable;

    protected $regency;

    public function __construct($regency)
    {
        $this->regency = $regency;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect([]);
    }

    public function headings(): array
    {
        return [
            'email_bps',
            'id_pasar',
        ];
    }

    public function title(): string
    {
        return 'Assignment';
    }
}

class MasterUserExportSheet implements FromQuery, ShouldQueue, WithHeadings, WithMapping, WithTitle
{
    use Exportable, Queueable;

    protected $regency;

    public function __construct($regency)
    {
        $this->regency = $regency;
    }

    public function query()
    {
        return User::query()->where('regency_id',  $this->regency)->role(['adminprov', 'adminkab', 'pml', 'operator']);
    }

    public function headings(): array
    {
        return [
            'email_bps',
            'nama_petugas',
        ];
    }

    public function map($user): array
    {
        return [
            $user->email,
            $user->firstname,
        ];
    }

    public function title(): string
    {
        return 'Petugas';
    }
}

class MasterMarketExportSheet implements FromQuery, ShouldQueue, WithHeadings, WithMapping, WithTitle
{
    use Exportable, Queueable;

    protected $regency;

    public function __construct($regency)
    {
        $this->regency = $regency;
    }

    public function query()
    {
        return Market::query()->where('regency_id',  $this->regency ?? '3578');
    }

    public function headings(): array
    {
        return [
            'id_pasar',
            'nama_pasar',
            'kabupaten',
            'kecamatan',
            'desa',
        ];
    }

    public function map($market): array
    {
        return [
            $market->id,
            $market->name,
            $market->regency != null ? ("[" . $market->regency->short_code . "] " .  $market->regency->name) : null,
            $market->subdistrict != null ? ("[" . $market->subdistrict->short_code . "] " .  $market->subdistrict->name) : null,
            $market->village != null ? ("[" . $market->village->short_code . "] " .  $market->village->name) : null,
        ];
    }

    public function title(): string
    {
        return 'Pasar';
    }
}

class MarketAssignmentExport implements WithMultipleSheets, ShouldQueue
{
    protected $regency;

    public function __construct($regency)
    {
        $this->regency = $regency;
    }

    public function sheets(): array
    {
        $sheets = [];
        $sheets[] = new MarketAssignmentExportSheet($this->regency);
        $sheets[] = new MasterUserExportSheet($this->regency);
        $sheets[] = new MasterMarketExportSheet($this->regency);

        return $sheets;
    }
}
