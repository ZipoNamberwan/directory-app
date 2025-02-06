<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'adminprov']);
        Role::create(['name' => 'adminkab']);
        Role::create(['name' => 'pcl']);
        Role::create(['name' => 'pml']);

        $pcl = User::create([
            'username' => 'pcl01@gmail.com',
            'email' => 'pcl01@gmail.com',
            'firstname' => 'PCL 01',
            'password' => Hash::make('123456'),
            'regency_id' => '3501',
        ]);
        $pcl->assignRole('pcl');

        $pml = User::create([
            'username' => 'pml01@gmail.com',
            'email' => 'pml01@gmail.com',
            'firstname' => 'PML 01',
            'password' => Hash::make('123456'),
            'regency_id' => '3501',
        ]);
        $pml->assignRole('pml');

        $adminkab = User::create([
            'username' => 'admin3501@gmail.com',
            'email' => 'admin3501@gmail.com',
            'firstname' => 'Admin 01',
            'password' => Hash::make('123456'),
            'regency_id' => '3501',
        ]);
        $adminkab->assignRole('adminkab');

        $adminprov = User::create([
            'username' => 'admin3500@gmail.com',
            'email' => 'admin3500@gmail.com',
            'firstname' => 'Admin Prov',
            'password' => Hash::make('123456'),
        ]);
        $adminprov->assignRole('adminprov');
    }
}
