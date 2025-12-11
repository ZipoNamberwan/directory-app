<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccessDuplicatePermissionSeederAdminKab extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::role(['adminkab'])->get();
        foreach ($users as $user) {
            $user->setPermissionAllDatabase(false, 'can_access_duplicate');
        }
    }
}
