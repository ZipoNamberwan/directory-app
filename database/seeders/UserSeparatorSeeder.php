<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class UserSeparatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $totalProcessed = 0;
        $totalUpdated = 0;

        $users = User::all();

        foreach ($users as $user) {
            $totalProcessed++;

            $kenarok = $user->is_wilkerstat_user;
            $kendedes = $user->projects()->exists();

            if (
                $user->is_kenarok_user !== $kenarok ||
                $user->is_kendedes_user !== $kendedes
            ) {
                $user->is_kenarok_user = $kenarok;
                $user->is_kendedes_user = $kendedes;
                $user->save();
                $totalUpdated++;
            }

            echo "Users processed: $totalProcessed\n";
            echo "Users updated: $totalUpdated\n";
        }
    }
}
