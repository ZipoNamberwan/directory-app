<?php

namespace App\Jobs;

use App\Models\AssignmentStatus;
use App\Models\Market;
use App\Models\MarketBusiness;
use App\Models\Regency;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class MarketBusinessExportJob implements ShouldQueue
{
    use Queueable;

    public $regencyId;
    public $marketId;
    public $uuid;
    /**
     * Create a new job instance.
     */
    public function __construct($regencyId, $marketId, $uuid)
    {
        $this->regencyId = $regencyId;
        $this->uuid = $uuid;
        $this->marketId = $marketId;

        AssignmentStatus::find($this->uuid)->update(['status' => 'loading',]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            AssignmentStatus::find($this->uuid)->update(['status' => 'loading']);

            if (!Storage::exists('market_business_raw')) {
                Storage::makeDirectory('market_business_raw');
            }

            $stream = fopen(Storage::path('/market_business_raw/' . $this->uuid . ".csv"), 'w+');

            $csv = Writer::createFromStream($stream);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');

            $csv->insertOne([
                'id',
                'Nama_Usaha',
                'Status_Bangunan',
                'Alamat',
                'Deskripsi',
                'Sektor',
                'Catatan',
                'Latitude',
                'Longitude',
                'Pasar',
                'User_Upload',
                'Kabupaten',
                'Kecamatan',
                'Desa',
            ]);

            $business = null;
            $regency = Regency::find($this->regencyId);
            $market = Market::find($this->marketId);

            $business = MarketBusiness::query();

            if ($regency) {
                $business->where('regency_id', $this->regencyId);
            }

            if ($market) {
                $business->where('market_id', $this->marketId);
            }

            $business
                ->with(['market.regency', 'market.subdistrict', 'market.village', 'user', 'regency'])
                ->chunk(1000, function ($businesses) use ($csv) {
                    foreach ($businesses as $row) {
                        $csv->insertOne([
                            $row->id,
                            $row->name,
                            $row->status,
                            $row->address,
                            $row->description,
                            $row->sector,
                            $row->note,
                            $row->latitude,
                            $row->longitude,
                            $row->market->name,
                            $row->user->firstname,
                            "[" . $row->market->regency->long_code . "] " . $row->market->regency->name,
                            "[" . $row->market->subdistrict->short_code . "] " . $row->market->subdistrict->name,
                            "[" . $row->market->village->short_code . "] " . $row->market->village->name,
                        ]);
                    }
                });

            fclose($stream);

            AssignmentStatus::find($this->uuid)->update(['status' => 'success']);
        } catch (Exception $e) {
            AssignmentStatus::find($this->uuid)->update(['status' => 'failed', 'message' => $e->getMessage()]);
        }
    }
}
