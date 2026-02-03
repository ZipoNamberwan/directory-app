<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\DatabaseSelector;
use App\Models\AreaPeriod;
use Illuminate\Support\Str;
use App\Models\Regency;
class RegencySeederV2 extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $version = 2;

        $periodId = AreaPeriod::where('period_version', $version)->first()->id;

        $regencies = [
            ['short_code' => '01', 'long_code' => '3501', 'name' => 'PACITAN'],
            ['short_code' => '02', 'long_code' => '3502', 'name' => 'PONOROGO'],
            ['short_code' => '03', 'long_code' => '3503', 'name' => 'TRENGGALEK'],
            ['short_code' => '04', 'long_code' => '3504', 'name' => 'TULUNGAGUNG'],
            ['short_code' => '05', 'long_code' => '3505', 'name' => 'BLITAR'],
            ['short_code' => '06', 'long_code' => '3506', 'name' => 'KEDIRI'],
            ['short_code' => '07', 'long_code' => '3507', 'name' => 'MALANG'],
            ['short_code' => '08', 'long_code' => '3508', 'name' => 'LUMAJANG'],
            ['short_code' => '09', 'long_code' => '3509', 'name' => 'JEMBER'],
            ['short_code' => '10', 'long_code' => '3510', 'name' => 'BANYUWANGI'],
            ['short_code' => '11', 'long_code' => '3511', 'name' => 'BONDOWOSO'],
            ['short_code' => '12', 'long_code' => '3512', 'name' => 'SITUBONDO'],
            ['short_code' => '13', 'long_code' => '3513', 'name' => 'PROBOLINGGO'],
            ['short_code' => '14', 'long_code' => '3514', 'name' => 'PASURUAN'],
            ['short_code' => '15', 'long_code' => '3515', 'name' => 'SIDOARJO'],
            ['short_code' => '16', 'long_code' => '3516', 'name' => 'MOJOKERTO'],
            ['short_code' => '17', 'long_code' => '3517', 'name' => 'JOMBANG'],
            ['short_code' => '18', 'long_code' => '3518', 'name' => 'NGANJUK'],
            ['short_code' => '19', 'long_code' => '3519', 'name' => 'MADIUN'],
            ['short_code' => '20', 'long_code' => '3520', 'name' => 'MAGETAN'],
            ['short_code' => '21', 'long_code' => '3521', 'name' => 'NGAWI'],
            ['short_code' => '22', 'long_code' => '3522', 'name' => 'BOJONEGORO'],
            ['short_code' => '23', 'long_code' => '3523', 'name' => 'TUBAN'],
            ['short_code' => '24', 'long_code' => '3524', 'name' => 'LAMONGAN'],
            ['short_code' => '25', 'long_code' => '3525', 'name' => 'GRESIK'],
            ['short_code' => '26', 'long_code' => '3526', 'name' => 'BANGKALAN'],
            ['short_code' => '27', 'long_code' => '3527', 'name' => 'SAMPANG'],
            ['short_code' => '28', 'long_code' => '3528', 'name' => 'PAMEKASAN'],
            ['short_code' => '29', 'long_code' => '3529', 'name' => 'SUMENEP'],
            ['short_code' => '71', 'long_code' => '3571', 'name' => 'KEDIRI'],
            ['short_code' => '72', 'long_code' => '3572', 'name' => 'BLITAR'],
            ['short_code' => '73', 'long_code' => '3573', 'name' => 'MALANG'],
            ['short_code' => '74', 'long_code' => '3574', 'name' => 'PROBOLINGGO'],
            ['short_code' => '75', 'long_code' => '3575', 'name' => 'PASURUAN'],
            ['short_code' => '76', 'long_code' => '3576', 'name' => 'MOJOKERTO'],
            ['short_code' => '77', 'long_code' => '3577', 'name' => 'MADIUN'],
            ['short_code' => '78', 'long_code' => '3578', 'name' => 'SURABAYA'],
            ['short_code' => '79', 'long_code' => '3579', 'name' => 'BATU'],
        ];

        $connections = DatabaseSelector::getListConnections();

        foreach ($regencies as $regency) {
            $regency['id'] = Str::uuid()->toString();
            $regency['area_period_id'] = $periodId;

            foreach ($connections as $connection) {
                Regency::on($connection)->create($regency);
            }
        }
    }
}
