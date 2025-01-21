<?php

namespace App\Exports;

use App\Models\CategorizedBusiness;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Throwable;

class CategorizedBusinessTemplateExport implements FromQuery, ShouldQueue
{
    use Exportable;

    protected $regency;

    public function __construct($regency)
    {
        $this->regency = $regency;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function query()
    {
        return CategorizedBusiness::query()->where('regency_id', $this->regency);
    }

    public function failed(Throwable $exception): void
    {
        // handle failed export
    }
}
