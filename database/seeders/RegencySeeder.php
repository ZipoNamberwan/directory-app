<?php

namespace Database\Seeders;

use App\Helpers\DatabaseSelector;
use App\Models\Regency;
use App\Models\Status;
use Illuminate\Database\Seeder;

class RegencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (DatabaseSelector::getListConnections() as $connection) {
            Status::on($connection)->create(['name' => 'Belum Dimutakhirkan', 'color' => 'secondary', 'order' => 1, 'code' => '-']);
            Status::on($connection)->create(['name' => 'Ada', 'color' => 'success', 'order' => 2, 'code' => '1']);
            Status::on($connection)->create(['name' => 'Tidak Ada', 'color' => 'danger', 'order' => 3, 'code' => '2']);
            Status::on($connection)->create(['name' => 'Pindah ke SLS lain', 'color' => 'danger', 'order' => 4, 'code' => '3']);
            Status::on($connection)->create(['name' => 'Bukan Cakupan SE', 'color' => 'danger', 'order' => 5, 'code' => '7']);
            Status::on($connection)->create(['id' => 90, 'name' => 'Baru', 'color' => 'info', 'order' => 6, 'code' => '8']);
            // Status::on($connection)->create(['name' => 'Tidak Tahu', 'color' => 'danger', 'order' => 6, 'code' => '9']);

            Regency::on($connection)->create(['short_code' => '01', 'long_code' => '3501', 'id' => '3501', 'name' => 'PACITAN',]);
            Regency::on($connection)->create(['short_code' => '02', 'long_code' => '3502', 'id' => '3502', 'name' => 'PONOROGO',]);
            Regency::on($connection)->create(['short_code' => '03', 'long_code' => '3503', 'id' => '3503', 'name' => 'TRENGGALEK',]);
            Regency::on($connection)->create(['short_code' => '04', 'long_code' => '3504', 'id' => '3504', 'name' => 'TULUNGAGUNG',]);
            Regency::on($connection)->create(['short_code' => '05', 'long_code' => '3505', 'id' => '3505', 'name' => 'BLITAR',]);
            Regency::on($connection)->create(['short_code' => '06', 'long_code' => '3506', 'id' => '3506', 'name' => 'KEDIRI',]);
            Regency::on($connection)->create(['short_code' => '07', 'long_code' => '3507', 'id' => '3507', 'name' => 'MALANG',]);
            Regency::on($connection)->create(['short_code' => '08', 'long_code' => '3508', 'id' => '3508', 'name' => 'LUMAJANG',]);
            Regency::on($connection)->create(['short_code' => '09', 'long_code' => '3509', 'id' => '3509', 'name' => 'JEMBER',]);
            Regency::on($connection)->create(['short_code' => '10', 'long_code' => '3510', 'id' => '3510', 'name' => 'BANYUWANGI',]);
            Regency::on($connection)->create(['short_code' => '11', 'long_code' => '3511', 'id' => '3511', 'name' => 'BONDOWOSO',]);
            Regency::on($connection)->create(['short_code' => '12', 'long_code' => '3512', 'id' => '3512', 'name' => 'SITUBONDO',]);
            Regency::on($connection)->create(['short_code' => '13', 'long_code' => '3513', 'id' => '3513', 'name' => 'PROBOLINGGO',]);
            Regency::on($connection)->create(['short_code' => '14', 'long_code' => '3514', 'id' => '3514', 'name' => 'PASURUAN',]);
            Regency::on($connection)->create(['short_code' => '15', 'long_code' => '3515', 'id' => '3515', 'name' => 'SIDOARJO',]);
            Regency::on($connection)->create(['short_code' => '16', 'long_code' => '3516', 'id' => '3516', 'name' => 'MOJOKERTO',]);
            Regency::on($connection)->create(['short_code' => '17', 'long_code' => '3517', 'id' => '3517', 'name' => 'JOMBANG',]);
            Regency::on($connection)->create(['short_code' => '18', 'long_code' => '3518', 'id' => '3518', 'name' => 'NGANJUK',]);
            Regency::on($connection)->create(['short_code' => '19', 'long_code' => '3519', 'id' => '3519', 'name' => 'MADIUN',]);
            Regency::on($connection)->create(['short_code' => '20', 'long_code' => '3520', 'id' => '3520', 'name' => 'MAGETAN',]);
            Regency::on($connection)->create(['short_code' => '21', 'long_code' => '3521', 'id' => '3521', 'name' => 'NGAWI',]);
            Regency::on($connection)->create(['short_code' => '22', 'long_code' => '3522', 'id' => '3522', 'name' => 'BOJONEGORO',]);
            Regency::on($connection)->create(['short_code' => '23', 'long_code' => '3523', 'id' => '3523', 'name' => 'TUBAN',]);
            Regency::on($connection)->create(['short_code' => '24', 'long_code' => '3524', 'id' => '3524', 'name' => 'LAMONGAN',]);
            Regency::on($connection)->create(['short_code' => '25', 'long_code' => '3525', 'id' => '3525', 'name' => 'GRESIK',]);
            Regency::on($connection)->create(['short_code' => '26', 'long_code' => '3526', 'id' => '3526', 'name' => 'BANGKALAN',]);
            Regency::on($connection)->create(['short_code' => '27', 'long_code' => '3527', 'id' => '3527', 'name' => 'SAMPANG',]);
            Regency::on($connection)->create(['short_code' => '28', 'long_code' => '3528', 'id' => '3528', 'name' => 'PAMEKASAN',]);
            Regency::on($connection)->create(['short_code' => '29', 'long_code' => '3529', 'id' => '3529', 'name' => 'SUMENEP',]);
            Regency::on($connection)->create(['short_code' => '71', 'long_code' => '3571', 'id' => '3571', 'name' => 'KEDIRI',]);
            Regency::on($connection)->create(['short_code' => '72', 'long_code' => '3572', 'id' => '3572', 'name' => 'BLITAR',]);
            Regency::on($connection)->create(['short_code' => '73', 'long_code' => '3573', 'id' => '3573', 'name' => 'MALANG',]);
            Regency::on($connection)->create(['short_code' => '74', 'long_code' => '3574', 'id' => '3574', 'name' => 'PROBOLINGGO',]);
            Regency::on($connection)->create(['short_code' => '75', 'long_code' => '3575', 'id' => '3575', 'name' => 'PASURUAN',]);
            Regency::on($connection)->create(['short_code' => '76', 'long_code' => '3576', 'id' => '3576', 'name' => 'MOJOKERTO',]);
            Regency::on($connection)->create(['short_code' => '77', 'long_code' => '3577', 'id' => '3577', 'name' => 'MADIUN',]);
            Regency::on($connection)->create(['short_code' => '78', 'long_code' => '3578', 'id' => '3578', 'name' => 'SURABAYA',]);
            Regency::on($connection)->create(['short_code' => '79', 'long_code' => '3579', 'id' => '3579', 'name' => 'BATU',]);
        }
    }
}
