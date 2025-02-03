<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class UserAssignmentExportSheet implements FromQuery, ShouldQueue, WithHeadings, WithMapping, WithTitle
{
    use Exportable, Queueable;

    protected $regency;

    public function __construct($regency)
    {
        $this->regency = $regency;
    }

    public function query()
    {
        return User::query()->where('regency_id',  $this->regency)->role(['pcl', 'pml']);
    }

    public function headings(): array
    {
        return [
            'Email',
            'Nama_Petugas',
            'Role'
        ];
    }

    public function map($user): array
    {
        return [
            $user->email,
            $user->firstname,
            $user->getRoleNames()->implode(', ')
        ];
    }

    public function title(): string
    {
        return 'Petugas';
    }
}
