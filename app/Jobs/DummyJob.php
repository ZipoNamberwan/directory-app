<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class DummyJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
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

        $pcl = User::create([
            'username' => 'admin3501@gmail.com',
            'email' => 'admin3501@gmail.com',
            'firstname' => 'Admin 01',
            'password' => Hash::make('123456'),
            'regency_id' => '3501',
        ]);
        $pcl->assignRole('adminkab');

        $pcl = User::create([
            'username' => 'admin3500@gmail.com',
            'email' => 'admin3500@gmail.com',
            'firstname' => 'Admin Prov',
            'password' => Hash::make('123456'),
        ]);
        $pcl->assignRole('adminkab');
    }
}
