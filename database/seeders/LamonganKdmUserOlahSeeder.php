<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LamonganKdmUserOlahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['firstname' => "SYAFIRA ARIN NABILA", 'email' => "Sakarep921@gmail.com", 'username' => "Sakarep921@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "MOCH. BUDI UTOMO, S.M", 'email' => "Soetomo085@gmail.com", 'username' => "Soetomo085@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "FIKY HESTIROCHA", 'email' => "fikyrocha8@gmail.com", 'username' => "fikyrocha8@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "ULYATUR ROSYIDAH", 'email' => "rosyidahulyatur@gmail.com", 'username' => "rosyidahulyatur@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "ANGGUN RIF'ATUL JANNAH", 'email' => "anggunarj@gmail.com", 'username' => "anggunarj@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "MUHAMMAD NASHRUDDIN FADLI", 'email' => "Nashruddin13065@gmail.com", 'username' => "Nashruddin13065@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "IMROTUL MUFIDAH", 'email' => "Mufidahimrotul11@gmail.com", 'username' => "Mufidahimrotul11@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "ARWAN DALU WIJONARKO", 'email' => "daluarwan@gmail.com", 'username' => "daluarwan@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "BETTA NUR OKTAVIA NINGSIH", 'email' => "oktaviabetta07@gmail.com", 'username' => "oktaviabetta07@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "MOH. MAFTUH", 'email' => "mmaftuh444@gmail.com", 'username' => "mmaftuh444@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "SHOIBATUL ISLAMIYA", 'email' => "Shoibatulislamiyah.10@gmail.com", 'username' => "Shoibatulislamiyah.10@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "ANIS RISMAWATI", 'email' => "anisrismawatiipa2@gmail.com", 'username' => "anisrismawatiipa2@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "ALIFATUL MUJAHADAH", 'email' => "alifatulmujahadah02@gmail.com", 'username' => "alifatulmujahadah02@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "NAILAL FAUZIYAH", 'email' => "fauziyahnaila@gmail.com", 'username' => "fauziyahnaila@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "MUHAMMAD RUSYDI AFIF", 'email' => "mafif83@gmail.com", 'username' => "mafif83@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "DIYAH ARDITASARI", 'email' => "diyaharditasari@gmail.com", 'username' => "diyaharditasari@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "ALAMUDDIN FIKRY HARIDH", 'email' => "fikryharidh11@gmail.com", 'username' => "fikryharidh11@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "DYASWARA ANANDA LESMONO", 'email' => "dyaswara.ananda@gmail.com", 'username' => "dyaswara.ananda@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "AHMAD SHIHABBUDDIN", 'email' => "syihabudinahmad137@gmail.com", 'username' => "syihabudinahmad137@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "S. LINDA ROHAMANA WULANSARI", 'email' => "wulansr748@gmail.com", 'username' => "wulansr748@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "FRINE AFIFAH PRAMADITA", 'email' => "afifahpramadita@gmail.com", 'username' => "afifahpramadita@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "ERVINA MUTIARA FIRDAUSY", 'email' => "ervina.mf@gmail.com", 'username' => "ervina.mf@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "BUNGA CITRA LESTARI", 'email' => "citrab076@gmail.com", 'username' => "citrab076@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "MOHAMMAD KEN AILA WIDYANANDA", 'email' => "mohammadken1967@gmail.com", 'username' => "mohammadken1967@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "UZLIFATUL JANNAH", 'email' => "uzlifatuljannah02@gmail.com", 'username' => "uzlifatuljannah02@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "MUHAMMAD SAIFULLAH ANAM", 'email' => "muhammadsaifullah0905@gmail.com", 'username' => "muhammadsaifullah0905@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "A RENDRA FIRDAUS", 'email' => "arendrafirdaus@gmail.com", 'username' => "arendrafirdaus@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "SALMAN AL KAFI", 'email' => "salman.alkafi.sa@gmail.com", 'username' => "salman.alkafi.sa@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "FANY ZIZATUL ALVIYAH", 'email' => "alviyahfany@gmail.com", 'username' => "alviyahfany@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "KELARA CELILIA", 'email' => "kelaracelilia6@gmail.com", 'username' => "kelaracelilia6@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "MULYANINGSIH", 'email' => "mulyakurniawan0@gmail.com", 'username' => "mulyakurniawan0@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "FEBRIYANTI INDAH LUTFIATIN", 'email' => "lfebriyanti2002@gmail.com", 'username' => "lfebriyanti2002@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "NUR AFINATUL FITRI", 'email' => "afinatulfitri@gmail.com", 'username' => "afinatulfitri@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "FAHIMA PUPUT NURJANAH", 'email' => "fahimapuput@gmail.com", 'username' => "fahimapuput@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "DICKY FEBRIANTO", 'email' => "dickyfff9@gmail.com", 'username' => "dickyfff9@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "NI'MATUS SHOLIHAH", 'email' => "sholihahnikmatus01@gmail.com", 'username' => "sholihahnikmatus01@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "PRASETIYAWATI", 'email' => "prasstiawaty@gmail.com", 'username' => "prasstiawaty@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "VIVID ROHMANIYAH", 'email' => "vivid.media19@gmail.com", 'username' => "vivid.media19@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "YAKUB FISABILLILLAH", 'email' => "yakubfisabillillah@gmail.com", 'username' => "yakubfisabillillah@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "NASRINA JIHAN LABIBAH", 'email' => "nasrinajihan@gmail.com", 'username' => "nasrinajihan@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "NITA EKA RAHAYU", 'email' => "nitaekarahayu19@gmail.com", 'username' => "nitaekarahayu19@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "BIKI AHYUNI ALFIATIN WIHDANY", 'email' => "bikiahyuniiiii@gmail.com", 'username' => "bikiahyuniiiii@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "A.KHOIRUL ANAM", 'email' => "ahmadkhoiruel70@gmail.com", 'username' => "ahmadkhoiruel70@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "RISKA RAHMA YANTI", 'email' => "rizkarahma254@gmail.com", 'username' => "rizkarahma254@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "FITRI NUR LAILI", 'email' => "nlfitri03@gmail.com", 'username' => "nlfitri03@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "SINTA APRILIA DWI RAHMAWATI", 'email' => "apriliadwisinta64@gmail.com", 'username' => "apriliadwisinta64@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
        ];

        foreach ($users as $user) {
            // check if user exists by email
            if (!User::where('email', $user['email'])->exists()) {
                $u = User::create([
                    'firstname' => $user['firstname'],
                    'email' => $user['email'],
                    'regency_id' => $user['regency_id'],
                    'organization_id' => $user['organization_id'],
                    'username' => $user['username'],
                    'password' => Hash::make('se26sukses'),
                    'is_kendedes_user' => true
                ]);
                $u->assignRoleAllDatabase('pml');
            }
        }
    }
}
