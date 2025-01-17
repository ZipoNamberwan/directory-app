<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([AreaSeeder::class]);
        $this->call([CategorizedBusinessSeeder::class]);
        $this->call([DummySeeder::class]);
    }
}
