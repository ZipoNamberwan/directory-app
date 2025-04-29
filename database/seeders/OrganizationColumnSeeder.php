<?php

namespace Database\Seeders;

use App\Helpers\DatabaseSelector;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationColumnSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (DatabaseSelector::getListConnections() as $connection) {
            DB::connection($connection)->table('markets')->update([
                'organization_id' => DB::raw('regency_id')
            ]);

            DB::connection($connection)->table('users')->update([
                'organization_id' => DB::raw("CASE 
                    WHEN regency_id IS NOT NULL THEN regency_id
                    ELSE '3500'
                END")
            ]);
        }
    }
}
