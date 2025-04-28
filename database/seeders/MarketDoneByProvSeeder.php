<?php

namespace Database\Seeders;

use App\Models\Market;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MarketDoneByProvSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $markets = [
            'PASAR KEDURUS PASAR BARU',
            'PASAR TRADISIONAL PAGESANGAN',
            'PASAR TRADISIONAL KARAH',
            'PASAR BAMBE DUKUH MENANGGAL',
            'PASAR GAYUNGSARI',
            'PASAR KETINTANG BARAT',
            'PASAR LKMK',
            'PASAR KREMPYENG PDK',
            'PASAR AZ- ZAITUN',
            'INFRASTRUKTUR 2 (PASAR KUTISARI BARU)',
            'PASAR TRADISIONAL',
            'PASAR KENDANGSARI',
            'PASAR PANDUK PANJANG JIWO',
            'PASAR DEPO SAMPAH',
            'PASAR GEDREK',
            'PASARAYA KARSAJAYA',
            'PASAR GUNUNG ANYAR',
            'PASAR PAHING',
            'PASAR RW 6 (PASAR SAMPOERNA)',
            'PASAR RUNGKUT BARU',
            'PASAR LKMK WONOREJO',
            'PASAR SWADAYA',
            'PASAR SWDAYA 1',
            'PASAR PENJARINGAN SARI',
            'PASAR SINAR BARU',
            'PASAR SOPONYONO',
            'PASAR NGINDEN',
            'PASAR SEMOLO',
            'PASAR KEPUTIH',
            'PASAR ASEM PAYUNG',
            'PASAR MLETO',
            'PASAR SEMALANG',
            'INFRAATRUKTUR 5',
            'PASAR TEMPUREJO',
            'PASAR YAMURI',
            'PASAR BUNGA BRATANG',
            'PASAR BURUNG BRATANG',
            'PASAR INPRES BRATANG',
            'PASAR PUCANG ANOM',
            'PASAR GUBENG KERTAJAYA',
            'PASAR MANYAR/ MENUR',
            'PASAR JOJORAN',
            'PASAR MOJO ARUM',
            'PASAR IKAN HIAS',
            'PASAR SURYA WONOKITRI',
            'PASAR WONOKROMO- DTC',
            'PASAR BENDUL MERISI',
            'PASAR MANGGA DUA',
            'PASAR KRUKAH',
            'PASAR PAKIS',
            'PASAR AMPEL (KAMBING)',
            'PASAR PEGIRIAN',
            'PASAR SUKODONO',
            'PASAR TRADISIONAL, AMPEL SUCI',
            'PASAR ASWOTOMO',
            'PASAR BONGKAR MUAT BUAH WONOKUSUMO',
            'PASAR MRUTU KALIANYAR',
            'PASAR WONOKUSUMO',
            'PASAR KOMPLEK HANG TUAH',
            'PASAR LAPAK 29',
            'PASAR MESS AMPEL',
            'PASAR TRADISIONAL JATIPURWO',
        ];

        Market::whereIn('name', $markets)
            ->where('regency_id', 3578)
            ->update(['done_by_prov' => true]);
    }
}
