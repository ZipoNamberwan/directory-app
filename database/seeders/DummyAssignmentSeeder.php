<?php

namespace Database\Seeders;

use App\Jobs\DummyAssignmentJob;
use Illuminate\Database\Seeder;

class DummyAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DummyAssignmentJob::dispatch();
    }
}
