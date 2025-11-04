<?php

namespace Database\Seeders;

use App\Helpers\DatabaseSelector;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AccessDuplicatePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (DatabaseSelector::getListConnections() as $connection) {
            Permission::on($connection)->firstOrCreate(
                ['name' => 'can_access_duplicate'], // attributes to check
                ['name' => 'can_access_duplicate']  // values to insert if not found
            );
        }

        $users = User::role(['adminprov'])->get();
        foreach ($users as $user) {
            $user->setPermissionAllDatabase(false, 'can_access_duplicate');
        }
    }
}
