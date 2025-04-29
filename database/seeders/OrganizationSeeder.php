<?php

namespace Database\Seeders;

use App\Helpers\DatabaseSelector;
use App\Models\Organization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        foreach (DatabaseSelector::getListConnections() as $connection) {
            Organization::on($connection)->create(['short_code' => '00', 'long_code' => '3500', 'id' => '3500', 'name' => 'BPS PROVINSI JAWA TIMUR',]);
            Organization::on($connection)->create(['short_code' => '01', 'long_code' => '3501', 'id' => '3501', 'name' => 'BPS KABUPATEN PACITAN',]);
            Organization::on($connection)->create(['short_code' => '02', 'long_code' => '3502', 'id' => '3502', 'name' => 'BPS KABUPATEN PONOROGO',]);
            Organization::on($connection)->create(['short_code' => '03', 'long_code' => '3503', 'id' => '3503', 'name' => 'BPS KABUPATEN TRENGGALEK',]);
            Organization::on($connection)->create(['short_code' => '04', 'long_code' => '3504', 'id' => '3504', 'name' => 'BPS KABUPATEN TULUNGAGUNG',]);
            Organization::on($connection)->create(['short_code' => '05', 'long_code' => '3505', 'id' => '3505', 'name' => 'BPS KABUPATEN BLITAR',]);
            Organization::on($connection)->create(['short_code' => '06', 'long_code' => '3506', 'id' => '3506', 'name' => 'BPS KABUPATEN KEDIRI',]);
            Organization::on($connection)->create(['short_code' => '07', 'long_code' => '3507', 'id' => '3507', 'name' => 'BPS KABUPATEN MALANG',]);
            Organization::on($connection)->create(['short_code' => '08', 'long_code' => '3508', 'id' => '3508', 'name' => 'BPS KABUPATEN LUMAJANG',]);
            Organization::on($connection)->create(['short_code' => '09', 'long_code' => '3509', 'id' => '3509', 'name' => 'BPS KABUPATEN JEMBER',]);
            Organization::on($connection)->create(['short_code' => '10', 'long_code' => '3510', 'id' => '3510', 'name' => 'BPS KABUPATEN BANYUWANGI',]);
            Organization::on($connection)->create(['short_code' => '11', 'long_code' => '3511', 'id' => '3511', 'name' => 'BPS KABUPATEN BONDOWOSO',]);
            Organization::on($connection)->create(['short_code' => '12', 'long_code' => '3512', 'id' => '3512', 'name' => 'BPS KABUPATEN SITUBONDO',]);
            Organization::on($connection)->create(['short_code' => '13', 'long_code' => '3513', 'id' => '3513', 'name' => 'BPS KABUPATEN PROBOLINGGO',]);
            Organization::on($connection)->create(['short_code' => '14', 'long_code' => '3514', 'id' => '3514', 'name' => 'BPS KABUPATEN PASURUAN',]);
            Organization::on($connection)->create(['short_code' => '15', 'long_code' => '3515', 'id' => '3515', 'name' => 'BPS KABUPATEN SIDOARJO',]);
            Organization::on($connection)->create(['short_code' => '16', 'long_code' => '3516', 'id' => '3516', 'name' => 'BPS KABUPATEN MOJOKERTO',]);
            Organization::on($connection)->create(['short_code' => '17', 'long_code' => '3517', 'id' => '3517', 'name' => 'BPS KABUPATEN JOMBANG',]);
            Organization::on($connection)->create(['short_code' => '18', 'long_code' => '3518', 'id' => '3518', 'name' => 'BPS KABUPATEN NGANJUK',]);
            Organization::on($connection)->create(['short_code' => '19', 'long_code' => '3519', 'id' => '3519', 'name' => 'BPS KABUPATEN MADIUN',]);
            Organization::on($connection)->create(['short_code' => '20', 'long_code' => '3520', 'id' => '3520', 'name' => 'BPS KABUPATEN MAGETAN',]);
            Organization::on($connection)->create(['short_code' => '21', 'long_code' => '3521', 'id' => '3521', 'name' => 'BPS KABUPATEN NGAWI',]);
            Organization::on($connection)->create(['short_code' => '22', 'long_code' => '3522', 'id' => '3522', 'name' => 'BPS KABUPATEN BOJONEGORO',]);
            Organization::on($connection)->create(['short_code' => '23', 'long_code' => '3523', 'id' => '3523', 'name' => 'BPS KABUPATEN TUBAN',]);
            Organization::on($connection)->create(['short_code' => '24', 'long_code' => '3524', 'id' => '3524', 'name' => 'BPS KABUPATEN LAMONGAN',]);
            Organization::on($connection)->create(['short_code' => '25', 'long_code' => '3525', 'id' => '3525', 'name' => 'BPS KABUPATEN GRESIK',]);
            Organization::on($connection)->create(['short_code' => '26', 'long_code' => '3526', 'id' => '3526', 'name' => 'BPS KABUPATEN BANGKALAN',]);
            Organization::on($connection)->create(['short_code' => '27', 'long_code' => '3527', 'id' => '3527', 'name' => 'BPS KABUPATEN SAMPANG',]);
            Organization::on($connection)->create(['short_code' => '28', 'long_code' => '3528', 'id' => '3528', 'name' => 'BPS KABUPATEN PAMEKASAN',]);
            Organization::on($connection)->create(['short_code' => '29', 'long_code' => '3529', 'id' => '3529', 'name' => 'BPS KABUPATEN SUMENEP',]);
            Organization::on($connection)->create(['short_code' => '71', 'long_code' => '3571', 'id' => '3571', 'name' => 'BPS KOTA KEDIRI',]);
            Organization::on($connection)->create(['short_code' => '72', 'long_code' => '3572', 'id' => '3572', 'name' => 'BPS KOTA BLITAR',]);
            Organization::on($connection)->create(['short_code' => '73', 'long_code' => '3573', 'id' => '3573', 'name' => 'BPS KOTA MALANG',]);
            Organization::on($connection)->create(['short_code' => '74', 'long_code' => '3574', 'id' => '3574', 'name' => 'BPS KOTA PROBOLINGGO',]);
            Organization::on($connection)->create(['short_code' => '75', 'long_code' => '3575', 'id' => '3575', 'name' => 'BPS KOTA PASURUAN',]);
            Organization::on($connection)->create(['short_code' => '76', 'long_code' => '3576', 'id' => '3576', 'name' => 'BPS KOTA MOJOKERTO',]);
            Organization::on($connection)->create(['short_code' => '77', 'long_code' => '3577', 'id' => '3577', 'name' => 'BPS KOTA MADIUN',]);
            Organization::on($connection)->create(['short_code' => '78', 'long_code' => '3578', 'id' => '3578', 'name' => 'BPS KOTA SURABAYA',]);
            Organization::on($connection)->create(['short_code' => '79', 'long_code' => '3579', 'id' => '3579', 'name' => 'BPS KOTA BATU',]);
        }
    }
}
