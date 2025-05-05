<?php

namespace Database\Seeders;

use App\Models\MarketType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChangeMarketTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $id = MarketType::where('name', 'Pasar')->first()->id;
        DB::table('markets')->update([
            'market_type_id' => $id
        ]);
    }
}
