<?php

namespace Database\Seeders;

use App\Models\Market;
use App\Models\MarketBusiness;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChangeMarketCompletionStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marketIds = MarketBusiness::distinct()->pluck('market_id');

        Market::whereIn('id', $marketIds)->update(['completion_status' => 'on going']);
    }
}
