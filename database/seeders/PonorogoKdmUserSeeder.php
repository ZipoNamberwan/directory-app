<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PonorogoKdmUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['firstname' => "Fitria Tresnawati", 'username' => "fitriatresna90@gmail.com", 'email' => "fitriatresna90@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Diah Ardianasari", 'username' => "ardianasaridiah@gmail.com", 'email' => "ardianasaridiah@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Dodi Priyantoro", 'username' => "dodipriyantoro1997@gmail.com", 'email' => "dodipriyantoro1997@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Edi Prasetyo", 'username' => "ep110977@gmail.com", 'email' => "ep110977@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Ely Bayinatul Multazamah", 'username' => "elymultazamah89@gmail.com", 'email' => "elymultazamah89@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Andilala Mansur", 'username' => "andilalam@gmail.com", 'email' => "andilalam@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Anton Risantono", 'username' => "risantono4@gmail.com", 'email' => "risantono4@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Asnanto", 'username' => "asnanto9@gmail.com", 'email' => "asnanto9@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Bonandir", 'username' => "Bonabonandir@gmail.com", 'email' => "Bonabonandir@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Budi Darmawan", 'username' => "darmawan140187@gmail.com", 'email' => "darmawan140187@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Danang Eka Budiman", 'username' => "debueka@gmail.com", 'email' => "debueka@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Joko Suprianto", 'username' => "joko.anto02@gmail.com", 'email' => "joko.anto02@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Kripsi Alfian", 'username' => "kripsialfian@gmail.com", 'email' => "kripsialfian@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Lia Surya Meiningrum", 'username' => "liaputrisurya@gmail.com", 'email' => "liaputrisurya@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Logisia Yuri Pertiwi", 'username' => "logisiayuri23@gmail.com", 'email' => "logisiayuri23@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Mafi Dwi Prambudi", 'username' => "dwiprambudiMuafi@gmail.com", 'email' => "dwiprambudiMuafi@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Moh. Abdul Rosit", 'username' => "abdul.rosit89@gmail.com", 'email' => "abdul.rosit89@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Muh. Mudasir", 'username' => "Muh.mudasir79@gmail.com", 'email' => "Muh.mudasir79@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Muhammad Ridwan Eka Wardani", 'username' => "Ridwanekawardani03@gmail.com", 'email' => "Ridwanekawardani03@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Mulyo Santoso", 'username' => "mulyathereds6@gmail.com", 'email' => "mulyathereds6@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Nikmatul Laila Rosida (Ima)", 'username' => "nikmatull365@gmail.com", 'email' => "nikmatull365@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Novita Ike Yulistriani", 'username' => "novitaike2892@gmail.com", 'email' => "novitaike2892@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Nur Wahyu Setiawan Nugroho", 'username' => "Wahyudragneel1922@gmail.com", 'email' => "Wahyudragneel1922@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Prastawa Prendy Pratama", 'username' => "prastawaprendy5@gmail.com", 'email' => "prastawaprendy5@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Puthut Waskito", 'username' => "puthut.waskito86@gmail.com", 'email' => "puthut.waskito86@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Septa Feriono", 'username' => "septaferi46@gmail.com", 'email' => "septaferi46@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Siti Muryani", 'username' => "sitimuryani1206@gmail.com", 'email' => "sitimuryani1206@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Suprapto", 'username' => "Supraptosp2020@gmail.com", 'email' => "Supraptosp2020@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Suprapto", 'username' => "Ismaddoxtoto@gmail.com", 'email' => "Ismaddoxtoto@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Supriyono", 'username' => "priyononjapan@gmail.com", 'email' => "priyononjapan@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Syamsul Arifin", 'username' => "riefien00@gmail.com", 'email' => "riefien00@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Teguh Cahyono", 'username' => "Techecahyono.tc@gmail.com", 'email' => "Techecahyono.tc@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Yusuf Arifin", 'username' => "ucupipin34@gmail.com", 'email' => "ucupipin34@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Miftahul Khoiri", 'username' => "choyr1994@gmail.com", 'email' => "choyr1994@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Jarwanto", 'username' => "ANTOW4568@gmail.com", 'email' => "ANTOW4568@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Bambang Wiswanto", 'username' => "Wiswantobambang@gmail.com", 'email' => "Wiswantobambang@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Ardian Susanto", 'username' => "ardiansusanto1979@gmail.com", 'email' => "ardiansusanto1979@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Endang Nurul Giani", 'username' => "endangnurulgiani@gmail.com", 'email' => "endangnurulgiani@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Djoko Sarwono", 'username' => "sarwonojoko34@gmail.com", 'email' => "sarwonojoko34@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Fajar Eko Budiarto", 'username' => "ptfajarlestaripers@gmail.com", 'email' => "ptfajarlestaripers@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Ributcahyoprastyo", 'username' => "baosanlor052@gmail.com", 'email' => "baosanlor052@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Fandika Kurnia Pratama", 'username' => "Fandikakurnia95@gmail.com", 'email' => "Fandikakurnia95@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Ihsan Widodo", 'username' => "ihsanwidodo0262@gmail.com", 'email' => "ihsanwidodo0262@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Farida Dyah Setianingrum", 'username' => "faridadyahs@gmail.com", 'email' => "faridadyahs@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Nuryanto", 'username' => "nuryanto82403@gmail.com", 'email' => "nuryanto82403@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Gigit Wirawan", 'username' => "gigitwirawan2@gmail.com", 'email' => "gigitwirawan2@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Wildan Mahmud Awali", 'username' => "wildanmahmud54@gmail.com", 'email' => "wildanmahmud54@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Hery Dwi Hartanto", 'username' => "dwiheri601@gmail.com", 'email' => "dwiheri601@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Hadi Wianto", 'username' => "Sodekpradana@gmail.com", 'email' => "Sodekpradana@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Habib Rama Dhani", 'username' => "dhaniakl2017@gmail.com", 'email' => "dhaniakl2017@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Rian Pratama", 'username' => "goesseng@gmail.com", 'email' => "goesseng@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Imam Qomaruddin", 'username' => "sekar.langit27@gmail.com", 'email' => "sekar.langit27@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Selfi Triana Mardani", 'username' => "selv68362@gmail.com", 'email' => "selv68362@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Siti Mungawanah", 'username' => "sitimungawanah50@gmail.com", 'email' => "sitimungawanah50@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Ummaha Mahya", 'username' => "mayamahya8@gmail.com", 'email' => "mayamahya8@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Edi Setiawan", 'username' => "e.setiawan23.es@gmail.com", 'email' => "e.setiawan23.es@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Hariadi Wibowo", 'username' => "Ardiwibowo2@gmail.com", 'email' => "Ardiwibowo2@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Hesti Yuniarti", 'username' => "hesti.yuniarti86@gmail.com", 'email' => "hesti.yuniarti86@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Henry Trisetiyohaji", 'username' => "Hemrytrisetiyohaji@gmail.com", 'email' => "Hemrytrisetiyohaji@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Indah Widayati", 'username' => "widayati.indah@gmail.com", 'email' => "widayati.indah@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Supriyanto", 'username' => "araryaarfan@gmail.com", 'email' => "araryaarfan@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Dhian Lutfi Syarrifuddin", 'username' => "Dhianlutfiakmal@gmail.com", 'email' => "Dhianlutfiakmal@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Triyono", 'username' => "bayangkakigunung@gmail.com", 'email' => "bayangkakigunung@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Eka Fettyana Andriyanti", 'username' => "eccaanderson0236@gmail.com", 'email' => "eccaanderson0236@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Triono", 'username' => "trionotristan683@gmail.com", 'email' => "trionotristan683@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Tunjung Dwi Angesti", 'username' => "tunjungda28@gmail.com", 'email' => "tunjungda28@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Imam Nahrowi", 'username' => "bayannahrowi@gmail.com", 'email' => "bayannahrowi@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Yusron Ihza Mahendra", 'username' => "budionosutini@gmail.com", 'email' => "budionosutini@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Karyono", 'username' => "maskar008@gmail.com", 'email' => "maskar008@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Priyono", 'username' => "priyonoggn1975@gmail.com", 'email' => "priyonoggn1975@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Dwi Harminto", 'username' => "dwiharminto22@gmail.com", 'email' => "dwiharminto22@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Edy Santoso", 'username' => "bungitung75@gmail.com", 'email' => "bungitung75@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Rina Susilo Dewi", 'username' => "rinas9441@gmail.com", 'email' => "rinas9441@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Rina Widowati", 'username' => "rinawidowati77@gmail.com", 'email' => "rinawidowati77@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Faridul Mustofa", 'username' => "faridulmustofa@gmail.com", 'email' => "faridulmustofa@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Inggrid Ratih Mardikawati", 'username' => "Inggridratih@ymail.com", 'email' => "Inggridratih@ymail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Kusmanto", 'username' => "Kusmantok73@gmail.com", 'email' => "Kusmantok73@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Muna Arip Saipudin", 'username' => "munhaaripsaipudin@gmail.com", 'email' => "munhaaripsaipudin@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Aam Setyawan", 'username' => "Setyawan270@gmail.com", 'email' => "Setyawan270@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Wasisto", 'username' => "wasisto1970@gmail.com", 'email' => "wasisto1970@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Siti Fatonah", 'username' => "sifatonah77@gmail.com", 'email' => "sifatonah77@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
            ['firstname' => "Rizky Nova Rizaputra", 'username' => "rizkynovar.p.57@gmail.com", 'email' => "rizkynovar.p.57@gmail.com", 'password' => Hash::make('se26sukses'), 'organization_id' => 3502, 'regency_id' => 3502,],
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
