<?php

namespace Database\Seeders;

use App\Helpers\DatabaseSelector;
use App\Models\MarketType;
use Illuminate\Database\Seeder;

class MarketTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (DatabaseSelector::getListConnections() as $connection) {
            MarketType::on($connection)->create(['name' => 'Pasar']);
            MarketType::on($connection)->create(['name' => 'Mall']);
            MarketType::on($connection)->create(['name' => 'Perkantoran']);
        }
    }
}
