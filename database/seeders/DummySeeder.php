<?php

namespace Database\Seeders;

use App\Jobs\DummyJob;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DummyJob::dispatch();
    }
}
