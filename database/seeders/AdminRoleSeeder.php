<?php

namespace Database\Seeders;

use App\Helpers\DatabaseSelector;
use App\Models\User;
use Exception;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (DatabaseSelector::getListConnections() as $connection) {
            if (!Role::on($connection)->where('name', 'viewer')->exists()) {
                Role::on($connection)->create(['name' => 'viewer']);
            }
            if (!Role::on($connection)->where('name', 'superadmin')->exists()) {
                Role::on($connection)->create(['name' => 'superadmin']);
            }
        }

        $emails = [
            'apriyanto.nugroho@bps.go.id',
            'dian.musvitasari@bps.go.id',
            'pamuji2@bps.go.id',
            'rony@bps.go.id',
            'ike.noor@bps.go.id',
            'dian.sari@bps.go.id',
            'dwi.irnawati@bps.go.id',
            'ardi@bps.go.id',
            'ekosusanto@bps.go.id',
            'rindyanita@bps.go.id',
            'devon@bps.go.id',
            'fega@bps.go.id',
            'arifkurn@bps.go.id',
            'binti@bps.go.id',
            'ambar.budhi@bps.go.id',
            'heru.purnomo@bps.go.id',
            'ikarahma@bps.go.id',
            'deddy.dahlianto@bps.go.id',
            'santi.d.r@bps.go.id',
            'dadanghermawan@bps.go.id',
            'elvanariska@bps.go.id',
            'diana.fatmawati@bps.go.id',
            'saras.wati@bps.go.id',
            'navy@bps.go.id',
            'nurhidayah@bps.go.id',
            'deatamaganes@bps.go.id',
            'sayu.widiari@bps.go.id',
            'yudianto@bps.go.id',
            'doni.indarto@bps.go.id',
            'winidya@bps.go.id',
            'fentielektriana@bps.go.id',
            'ronihar@bps.go.id',
            'dedy.sujarwadi@bps.go.id',
            'irawan.hp@bps.go.id',
            'debita.tejo@bps.go.id',
            'indrajati@bps.go.id',
            'lamatur@bps.go.id',
            'rafiqa.zein@bps.go.id',
            'aditya.yudistira@bps.go.id',
            'imasartika@bps.go.id',
            'arifwi@bps.go.id',
            'nur.sakinah@bps.go.id',
            'nisaul.khusna@bps.go.id',
            'dicky@bps.go.id',
            'suifatiharangkuti@bps.go.id',
            'syaifulmutaqin@bps.go.id',
            'jokokn@bps.go.id',
            'debby.nv@bps.go.id',
            'christiayu@bps.go.id',
            'eka.rahayu@bps.go.id',
            'estinur@bps.go.id',
            'nurlaila.okta@bps.go.id',
            'sony@bps.go.id',
            'arief.aji@bps.go.id',
            'samsulbakhri@bps.go.id',
            'afina.latifa@bps.go.id',
            'chandras@bps.go.id',
            'riskandri@bps.go.id',
            'khusnul.kotimah@bps.go.id',
            'adiangga@bps.go.id',
            'koernia@bps.go.id',
            'yudhip@bps.go.id',
            'tonyhartono@bps.go.id',
            'saurina.banjarnahor@bps.go.id',
            'henipangestu@bps.go.id',
            'anik.dm@bps.go.id',
            'satria.wibawa@bps.go.id',
            'prasetiyo@bps.go.id',
        ];

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            if ($user != null) {
                $user->assignRoleAllDatabase(['adminkab']);
            }
        }

        $emails = [
            'zulki@bps.go.id',
        ];

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            if ($user != null) {
                $user->assignRoleAllDatabase(['viewer']);
            }
        }

        $emails = [
            'ayis@bps.go.id',
            'dhonieko@bps.go.id',
            'wahyu.razi@bps.go.id',
        ];
        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            if ($user != null) {
                $user->assignRoleAllDatabase(['adminprov']);
            }
        }
    }
}
