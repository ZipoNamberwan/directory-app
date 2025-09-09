<?php

namespace Database\Seeders;

use App\Helpers\DatabaseSelector;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class GenerateUserCanEditPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (DatabaseSelector::getListConnections() as $connection) {
            Permission::on($connection)->firstOrCreate(
                ['name' => 'edit_business'], // attributes to check
                ['name' => 'edit_business']  // values to insert if not found
            );
            Permission::on($connection)->firstOrCreate(
                ['name' => 'delete_business'], // attributes to check
                ['name' => 'delete_business']  // values to insert if not found
            );
        }

        $users = User::role(['adminprov', 'adminkab'])->get();
        foreach ($users as $user) {
            $user->setPermissionAllDatabase(true, 'edit_business', 'delete_business');
        }
    }
}
