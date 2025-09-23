<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdditionalKdmUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['firstname' => "Sri Murti", 'email' => "srimurti172@gmail.com", 'username' => "srimurti172@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Ugik Ubaidillah", 'email' => "ugikubaid@gmail.com", 'username' => "ugikubaid@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Mohammad Afifuddin", 'email' => "salamlestari1980@gmail.com", 'username' => "salamlestari1980@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Muhammad Farihul Amin", 'email' => "farihulmind26@gmail.com", 'username' => "farihulmind26@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Moh. Bagus Sam Ady", 'email' => "indrabayan197@gmail.com", 'username' => "indrabayan197@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Muthohirotul lishoimi", 'email' => "atariswijaya@gmail.com", 'username' => "atariswijaya@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Syafira Arin Nabila", 'email' => "syafiranabila749@gmail.com", 'username' => "syafiranabila749@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Fery Nur Cahyati ", 'email' => "verynur75@gmail.com", 'username' => "verynur75@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Nailus Saadah", 'email' => "nailus.saadah12@gmail.com", 'username' => "nailus.saadah12@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Jati Nur Daini", 'email' => "jatidaini@gmail.com", 'username' => "jatidaini@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Ma'sum", 'email' => "polomaksum55@gmail.com", 'username' => "polomaksum55@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Syahfitri", 'email' => "syahfitri.file@gmail.com", 'username' => "syahfitri.file@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Lutfiah", 'email' => "lutfiah0706@gmail.com", 'username' => "lutfiah0706@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Dewi Firdaus", 'email' => "dwifirdaus60@gmail.com", 'username' => "dwifirdaus60@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Fitri Rochmawati", 'email' => "vithree.ipit@gmail.com", 'username' => "vithree.ipit@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Adi Putra Setyawan", 'email' => "adiputra986@gmail.com", 'username' => "adiputra986@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Anzaswati", 'email' => "adielmaysa@gmail.com", 'username' => "adielmaysa@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Ishmatun Ni'mah", 'email' => "ishmatunn29@gmail.com", 'username' => "ishmatunn29@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Budiono", 'email' => "budionobanjarwati@gmail.com", 'username' => "budionobanjarwati@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Tasya Syalsabela", 'email' => "tasyasalsabela366@gmail.com", 'username' => "tasyasalsabela366@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Suliyanah", 'email' => "zviviana668@gmail.com", 'username' => "zviviana668@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Ririn Mega", 'email' => "ririnsetia2@gmail.com", 'username' => "ririnsetia2@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Retno Sri Puspitorini", 'email' => "rindurini1379@gmail.com", 'username' => "rindurini1379@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Dian Febrianita Adi Putri, S.Agr", 'email' => "dianfebrianita5@gmail.com", 'username' => "dianfebrianita5@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Kairil Efendi", 'email' => "kairilefendi93@gmail.com", 'username' => "kairilefendi93@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Siti Komariyah", 'email' => "sitikomariyah121978@gmail.com", 'username' => "sitikomariyah121978@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Dian Siti Rukhmana", 'email' => "dianrukhmana5850@gmail.com", 'username' => "dianrukhmana5850@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Nindy Ayu Khoiriyah", 'email' => "nindyayukhoiriyah27@gmail.com", 'username' => "nindyayukhoiriyah27@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Rindi Dwi Kurniawati", 'email' => "rindidwikurnia23@gmail.com", 'username' => "rindidwikurnia23@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Yunanifa", 'email' => "ayuna9253@gmail.com (27", 'username' => "ayuna9253@gmail.com (27", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Rodlotul Jannah", 'email' => "rrjannah22@gmail.com", 'username' => "rrjannah22@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Zulfina Ali Itsnaeni", 'email' => "zulfina0910@gmail.com", 'username' => "zulfina0910@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Ulupi Wulan", 'email' => "akiramedinanayyara24@gmail.com", 'username' => "akiramedinanayyara24@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Siti Aisah", 'email' => "sitiaisyahsrrj9@gmail.com", 'username' => "sitiaisyahsrrj9@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Menik Ariyanti", 'email' => "menikazzahra@gmail.com", 'username' => "menikazzahra@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Eka Ari Susanti", 'email' => "ekaariesusanti@gmail.com", 'username' => "ekaariesusanti@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Nurul Hidayah", 'email' => "uunynurul@gmail.com (26", 'username' => "uunynurul@gmail.com (26", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Yeni Rohmana", 'email' => "yennimegumey184@gmail.com", 'username' => "yennimegumey184@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Ahmad Is'adur Rofiq", 'email' => "isadurrofiq26@gmail.com", 'username' => "isadurrofiq26@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3524, 'regency_id' => 3524,],
            ['firstname' => "Avilia Indyra Rizki Sumarsono", 'email' => "avilia.indira29@gmail.com", 'username' => "avilia.indira29@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Luthfiah Damayanti ", 'email' => "luthfiahdamayanti49@gmail.com", 'username' => "luthfiahdamayanti49@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Ridwan Prasetya Utomo", 'email' => "ridwanprasetya93@gmail.com", 'username' => "ridwanprasetya93@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Eli Umaya", 'email' => "eliumaya9@gmail.com", 'username' => "eliumaya9@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Roykatul Jannah", 'email' => "ichaberline@gmail.com", 'username' => "ichaberline@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Alwin Tentrem Naluri", 'email' => "alwintentremnaluri@gmail.com", 'username' => "alwintentremnaluri@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Rizky Aprilia Bunga Prastika", 'email' => "rizky6984@gmail.com", 'username' => "rizky6984@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Annisa Luthfiatu Azzahra", 'email' => "annisaluthfiatuaz@gmail.com", 'username' => "annisaluthfiatuaz@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Agnes Ayudya Putri Ardhana", 'email' => "ayudyaagnes@gmail.com", 'username' => "ayudyaagnes@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Avilia Indyra Rizki Sumarsono", 'email' => "avilia.indira29@gmail.com", 'username' => "avilia.indira29@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Ferystia Eka Anggraini", 'email' => "aisteagirl89@gmail.com", 'username' => "aisteagirl89@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Wasis Abidin", 'email' => "4labid@gmail.com", 'username' => "4labid@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Luthfiah Damayanti ", 'email' => "luthfiahdamayanti49@gmail.com", 'username' => "luthfiahdamayanti49@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Bagas Sentana", 'email' => "bagassentana123@gmail.com", 'username' => "bagassentana123@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Ridwan Prasetya Utomo", 'email' => "ridwanprasetya93@gmail.com", 'username' => "ridwanprasetya93@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Bagus Setyaning Pambudi", 'email' => "baguspcl3520@gmail.com", 'username' => "baguspcl3520@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Kenes Annesia Herlambang", 'email' => "kenesannesia@gmail.com", 'username' => "kenesannesia@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Shinta Patma Sari", 'email' => "shinta.patma@gmail.com", 'username' => "shinta.patma@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Eli Umaya", 'email' => "eliumaya9@gmail.com", 'username' => "eliumaya9@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Roykatul Jannah", 'email' => "ichaberline@gmail.com", 'username' => "ichaberline@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Tiara Lestariansyah", 'email' => "mekarlestariansyah@gmail.com", 'username' => "mekarlestariansyah@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Peggy Sukma Melati", 'email' => "peggysukma51@gmail.com", 'username' => "peggysukma51@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Yasindra Eka Brillyan", 'email' => "yasindraeka1@gmail.com", 'username' => "yasindraeka1@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Alwin Tentrem Naluri", 'email' => "alwintentremnaluri@gmail.com", 'username' => "alwintentremnaluri@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Sandra Sagita Ermayanti", 'email' => "sandrasagitaermayanti@gmail.com", 'username' => "sandrasagitaermayanti@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Steven Yanuar Riskitianto", 'email' => "yanuarsteven9@gmail.com", 'username' => "yanuarsteven9@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Siti Rokhmi Fidiyanti", 'email' => "fidiyantialvi83@gmail.com", 'username' => "fidiyantialvi83@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Evan Juniar Tryyoga", 'email' => "evanjunior717@gmail.com", 'username' => "evanjunior717@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Rizky Aprilia Bunga Prastika", 'email' => "rizky6984@gmail.com", 'username' => "rizky6984@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Annisa Luthfiatu Azzahra", 'email' => "annisaluthfiatuaz@gmail.com", 'username' => "annisaluthfiatuaz@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Agnes Ayudya Putri Ardhana", 'email' => "ayudyaagnes@gmail.com", 'username' => "ayudyaagnes@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Lola Deavi Novie Yana", 'email' => "lolakiki56@gmail.com", 'username' => "lolakiki56@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
            ['firstname' => "Iwan Tri Cahyono", 'email' => "iwantri47@gmail.com", 'username' => "iwantri47@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3520, 'regency_id' => 3520,],
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
