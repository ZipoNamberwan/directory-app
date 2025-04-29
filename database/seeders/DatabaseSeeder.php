<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([RegencySeeder::class]);
        $this->call([DummySeeder::class]);
        $this->call([AreaSeeder::class]);
        $this->call([BusinessSeeder::class]);
        // $this->call([DummyAssignmentSeeder::class]);
        $this->call([DummyMarketSeeder::class]);
        $this->call([UserSeeder::class]);
        $this->call([MarketSeeder::class]);
        $this->call([AdminRoleSeeder::class]);

        $this->call([OrganizationSeeder::class]);
        $this->call([OrganizationColumnSeeder::class]);
        $this->call([MarketDoneByProvSeeder::class]);

    }
}
