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
        $this->call([AreaSeeder::class]);
        $this->call([SlsBusinessSeeder::class]);
        $this->call([NonSlsBusinessSeeder::class]);
        $this->call([DummySeeder::class]);
    }
}
