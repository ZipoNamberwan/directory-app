<?php

namespace Database\Seeders;

use App\Helpers\DatabaseSelector;
use App\Models\AnomalyType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnomalyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AnomalyType::create([
            'name' => 'Nama Usaha Tidak Wajar',
            'code' => 'L1',
            'description' => 'Nama Usaha Tidak Wajar',
        ]);

        AnomalyType::create([
            'name' => 'Deksripsi Usaha Tidak Wajar',
            'code' => 'L2',
            'description' => 'Deksripsi Usaha Tidak Wajar',
        ]);

        AnomalyType::create([
            'name' => 'Alamat Usaha Tidak Wajar',
            'code' => 'L3',
            'description' => 'Alamat Usaha Tidak Wajar',
        ]);

        AnomalyType::create([
            'name' => 'Pemilik Usaha Tidak Wajar',
            'code' => 'L4',
            'description' => 'Pemilik Usaha Tidak Wajar',
        ]);

        AnomalyType::create([
            'name' => 'Lektor Usaha Keliru',
            'code' => 'L5',
            'description' => 'Sektor Usaha Tidak Boleh A, O atau T',
        ]);
    }
}
