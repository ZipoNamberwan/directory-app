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
        return User::query()->where('regency_id',  $this->regency)->role(['pml', 'operator']);
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
        return Market::query()->where('regency_id',  $this->regency);
    }

    public function headings(): array
    {
        return [
            'id_pasar',
            'nama_pasar',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
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
