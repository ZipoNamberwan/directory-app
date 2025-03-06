<?php

namespace Database\Seeders;

use App\Helpers\DatabaseSelector;
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
        foreach (DatabaseSelector::getListConnections() as $connection) {
            Role::on($connection)->create(['name' => 'adminprov']);
            Role::on($connection)->create(['name' => 'adminkab']);
            Role::on($connection)->create(['name' => 'pcl']);
            Role::on($connection)->create(['name' => 'pml']);
            Role::on($connection)->create(['name' => 'operator']);
        }

        $pcl = User::create([
            'username' => 'pcl01@gmail.com',
            'email' => 'pcl01@gmail.com',
            'firstname' => 'PCL 01',
            'password' => Hash::make('123456'),
            'regency_id' => '3501',
        ]);
        $pcl->assignRoleAllDatabase('pcl');

        $pml = User::create([
            'username' => 'pml01@gmail.com',
            'email' => 'pml01@gmail.com',
            'firstname' => 'PML 01',
            'password' => Hash::make('123456'),
            'regency_id' => '3501',
        ]);
        $pml->assignRoleAllDatabase('pml');

        $adminprov = User::create([
            'username' => 'admin3500@gmail.com',
            'email' => 'admin3500@gmail.com',
            'firstname' => 'Admin Prov',
            'password' => Hash::make('inYourDre4m'),
            'must_change_password' => false
        ]);
        $adminprov->assignRoleAllDatabase('adminprov');

        $adminkab = User::create(['username' => 'bps3501@bps.go.id', 'email' => 'bps3501@bps.go.id', 'firstname' => 'Admin 3501', 'password' => Hash::make('se26sukses'), 'regency_id' => '3501',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3502@bps.go.id', 'email' => 'bps3502@bps.go.id', 'firstname' => 'Admin 3502', 'password' => Hash::make('se26sukses'), 'regency_id' => '3502',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3503@bps.go.id', 'email' => 'bps3503@bps.go.id', 'firstname' => 'Admin 3503', 'password' => Hash::make('se26sukses'), 'regency_id' => '3503',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3504@bps.go.id', 'email' => 'bps3504@bps.go.id', 'firstname' => 'Admin 3504', 'password' => Hash::make('se26sukses'), 'regency_id' => '3504',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3505@bps.go.id', 'email' => 'bps3505@bps.go.id', 'firstname' => 'Admin 3505', 'password' => Hash::make('se26sukses'), 'regency_id' => '3505',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3506@bps.go.id', 'email' => 'bps3506@bps.go.id', 'firstname' => 'Admin 3506', 'password' => Hash::make('se26sukses'), 'regency_id' => '3506',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3507@bps.go.id', 'email' => 'bps3507@bps.go.id', 'firstname' => 'Admin 3507', 'password' => Hash::make('se26sukses'), 'regency_id' => '3507',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3508@bps.go.id', 'email' => 'bps3508@bps.go.id', 'firstname' => 'Admin 3508', 'password' => Hash::make('se26sukses'), 'regency_id' => '3508',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3509@bps.go.id', 'email' => 'bps3509@bps.go.id', 'firstname' => 'Admin 3509', 'password' => Hash::make('se26sukses'), 'regency_id' => '3509',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3510@bps.go.id', 'email' => 'bps3510@bps.go.id', 'firstname' => 'Admin 3510', 'password' => Hash::make('se26sukses'), 'regency_id' => '3510',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3511@bps.go.id', 'email' => 'bps3511@bps.go.id', 'firstname' => 'Admin 3511', 'password' => Hash::make('se26sukses'), 'regency_id' => '3511',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3512@bps.go.id', 'email' => 'bps3512@bps.go.id', 'firstname' => 'Admin 3512', 'password' => Hash::make('se26sukses'), 'regency_id' => '3512',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3513@bps.go.id', 'email' => 'bps3513@bps.go.id', 'firstname' => 'Admin 3513', 'password' => Hash::make('se26sukses'), 'regency_id' => '3513',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3514@bps.go.id', 'email' => 'bps3514@bps.go.id', 'firstname' => 'Admin 3514', 'password' => Hash::make('se26sukses'), 'regency_id' => '3514',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3515@bps.go.id', 'email' => 'bps3515@bps.go.id', 'firstname' => 'Admin 3515', 'password' => Hash::make('se26sukses'), 'regency_id' => '3515',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3516@bps.go.id', 'email' => 'bps3516@bps.go.id', 'firstname' => 'Admin 3516', 'password' => Hash::make('se26sukses'), 'regency_id' => '3516',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3517@bps.go.id', 'email' => 'bps3517@bps.go.id', 'firstname' => 'Admin 3517', 'password' => Hash::make('se26sukses'), 'regency_id' => '3517',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3518@bps.go.id', 'email' => 'bps3518@bps.go.id', 'firstname' => 'Admin 3518', 'password' => Hash::make('se26sukses'), 'regency_id' => '3518',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3519@bps.go.id', 'email' => 'bps3519@bps.go.id', 'firstname' => 'Admin 3519', 'password' => Hash::make('se26sukses'), 'regency_id' => '3519',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3520@bps.go.id', 'email' => 'bps3520@bps.go.id', 'firstname' => 'Admin 3520', 'password' => Hash::make('se26sukses'), 'regency_id' => '3520',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3521@bps.go.id', 'email' => 'bps3521@bps.go.id', 'firstname' => 'Admin 3521', 'password' => Hash::make('se26sukses'), 'regency_id' => '3521',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3522@bps.go.id', 'email' => 'bps3522@bps.go.id', 'firstname' => 'Admin 3522', 'password' => Hash::make('se26sukses'), 'regency_id' => '3522',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3523@bps.go.id', 'email' => 'bps3523@bps.go.id', 'firstname' => 'Admin 3523', 'password' => Hash::make('se26sukses'), 'regency_id' => '3523',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3524@bps.go.id', 'email' => 'bps3524@bps.go.id', 'firstname' => 'Admin 3524', 'password' => Hash::make('se26sukses'), 'regency_id' => '3524',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3525@bps.go.id', 'email' => 'bps3525@bps.go.id', 'firstname' => 'Admin 3525', 'password' => Hash::make('se26sukses'), 'regency_id' => '3525',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3526@bps.go.id', 'email' => 'bps3526@bps.go.id', 'firstname' => 'Admin 3526', 'password' => Hash::make('se26sukses'), 'regency_id' => '3526',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3527@bps.go.id', 'email' => 'bps3527@bps.go.id', 'firstname' => 'Admin 3527', 'password' => Hash::make('se26sukses'), 'regency_id' => '3527',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3528@bps.go.id', 'email' => 'bps3528@bps.go.id', 'firstname' => 'Admin 3528', 'password' => Hash::make('se26sukses'), 'regency_id' => '3528',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3529@bps.go.id', 'email' => 'bps3529@bps.go.id', 'firstname' => 'Admin 3529', 'password' => Hash::make('se26sukses'), 'regency_id' => '3529',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3571@bps.go.id', 'email' => 'bps3571@bps.go.id', 'firstname' => 'Admin 3571', 'password' => Hash::make('se26sukses'), 'regency_id' => '3571',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3572@bps.go.id', 'email' => 'bps3572@bps.go.id', 'firstname' => 'Admin 3572', 'password' => Hash::make('se26sukses'), 'regency_id' => '3572',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3573@bps.go.id', 'email' => 'bps3573@bps.go.id', 'firstname' => 'Admin 3573', 'password' => Hash::make('se26sukses'), 'regency_id' => '3573',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3574@bps.go.id', 'email' => 'bps3574@bps.go.id', 'firstname' => 'Admin 3574', 'password' => Hash::make('se26sukses'), 'regency_id' => '3574',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3575@bps.go.id', 'email' => 'bps3575@bps.go.id', 'firstname' => 'Admin 3575', 'password' => Hash::make('se26sukses'), 'regency_id' => '3575',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3576@bps.go.id', 'email' => 'bps3576@bps.go.id', 'firstname' => 'Admin 3576', 'password' => Hash::make('se26sukses'), 'regency_id' => '3576',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3577@bps.go.id', 'email' => 'bps3577@bps.go.id', 'firstname' => 'Admin 3577', 'password' => Hash::make('se26sukses'), 'regency_id' => '3577',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3578@bps.go.id', 'email' => 'bps3578@bps.go.id', 'firstname' => 'Admin 3578', 'password' => Hash::make('se26sukses'), 'regency_id' => '3578',]);
        $adminkab->assignRoleAllDatabase('adminkab');
        $adminkab = User::create(['username' => 'bps3579@bps.go.id', 'email' => 'bps3579@bps.go.id', 'firstname' => 'Admin 3579', 'password' => Hash::make('se26sukses'), 'regency_id' => '3579',]);
        $adminkab->assignRoleAllDatabase('adminkab');
    }
}
