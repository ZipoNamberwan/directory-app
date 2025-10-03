<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserActingContext;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserActingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['email' => 'ksatrio.jati@bps.go.id', 'acting_org_id' => '3501'],
            ['email' => 'joko.ade@bps.go.id', 'acting_org_id' => '3502'],
            ['email' => 'sunufahri@bps.go.id', 'acting_org_id' => '3503'],
            ['email' => 'dhonieko@bps.go.id', 'acting_org_id' => '3504'],
            ['email' => 'sandra.logaritma@bps.go.id', 'acting_org_id' => '3505'],
            ['email' => 'sasmitohari@bps.go.id', 'acting_org_id' => '3506'],
            ['email' => 'rahma.nuryanti@bps.go.id', 'acting_org_id' => '3519'],
            ['email' => 'chus_chot@bps.go.id', 'acting_org_id' => '3520'],
            ['email' => 'dyahreni@bps.go.id', 'acting_org_id' => '3517'],
            ['email' => 'yenirahma@bps.go.id', 'acting_org_id' => '3518'],
            ['email' => 'ryan.januardi@bps.go.id', 'acting_org_id' => '3521'],
            ['email' => 'ahmad.arafat@bps.go.id', 'acting_org_id' => '3522'],
            ['email' => 'uswatunnurula@bps.go.id', 'acting_org_id' => '3523'],
            ['email' => 'nur.jannati@bps.go.id', 'acting_org_id' => '3524'],
            ['email' => 'kartika.yn@bps.go.id', 'acting_org_id' => '3525'],
            ['email' => 'putrisheilah-pppk@bps.go.id', 'acting_org_id' => '3507'],
            ['email' => 'hisbul.wathoni@bps.go.id', 'acting_org_id' => '3508'],
            ['email' => 'ahmad.rifan@bps.go.id', 'acting_org_id' => '3509'],
            ['email' => 'wahyu.razi@bps.go.id', 'acting_org_id' => '3510'],
            ['email' => 'azza@bps.go.id', 'acting_org_id' => '3511'],
            ['email' => 'aminsanikertiyasa@bps.go.id', 'acting_org_id' => '3512'],
            ['email' => 'edris@bps.go.id', 'acting_org_id' => '3513'],
            ['email' => 'chindy.pratiwi@bps.go.id', 'acting_org_id' => '3514'],
            ['email' => 'agusta@bps.go.id', 'acting_org_id' => '3515'],
            ['email' => 'pembayun@bps.go.id', 'acting_org_id' => '3516'],
            ['email' => 'amin.fathullah@bps.go.id', 'acting_org_id' => '3528'],
            ['email' => 'saryono@bps.go.id', 'acting_org_id' => '3529'],
            ['email' => 'baiqirfa@bps.go.id', 'acting_org_id' => '3573'],
            ['email' => 'ayis@bps.go.id', 'acting_org_id' => '3578'],
            ['email' => 'bahrul.ulum@bps.go.id', 'acting_org_id' => '3526'],
            ['email' => 'bams@bps.go.id', 'acting_org_id' => '3527'],
            ['email' => 'rikamujiastuti@bps.go.id', 'acting_org_id' => '3571'],
            ['email' => 'irdienaizza@bps.go.id', 'acting_org_id' => '3572'],
            ['email' => 'widia@bps.go.id', 'acting_org_id' => '3574'],
            ['email' => 'aldizah@bps.go.id', 'acting_org_id' => '3575'],
            ['email' => 'nur.roudlotul@bps.go.id', 'acting_org_id' => '3576'],
            ['email' => 'adelia.alifiany@bps.go.id', 'acting_org_id' => '3577'],
            ['email' => 'luxy.lutfiana@bps.go.id', 'acting_org_id' => '3579'],
        ];

        foreach ($data as $dt) {
            $user = User::where('email', $dt['email'])->first();
            if ($user) {
                UserActingContext::create([
                    'user_id' => $user->id,
                    'acting_org_id' => $dt['acting_org_id'],
                    'acting_role' => 'adminkab',
                    'active' => false,
                ]);
            }
        }
    }
}
