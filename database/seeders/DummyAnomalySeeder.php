<?php

namespace Database\Seeders;

use App\Models\AnomalyRepair;
use App\Models\SupplementBusiness;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class DummyAnomalySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $business = SupplementBusiness::first();
        $repair = $business->anomalies()->create([
            'id' => Str::uuid(),
            'status' => 'notconfirmed',
            'anomaly_type_id' => 2,
            'old_value' => 'Food',
            'note' => 'Mismatch sector',
            'user_id' => auth()->id(),
        ]);
    }
}
