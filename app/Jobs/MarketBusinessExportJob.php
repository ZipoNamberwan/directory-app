<?php

namespace App\Jobs;

use App\Models\AssignmentStatus;
use App\Models\Market;
use App\Models\MarketBusiness;
use App\Models\Organization;
use App\Models\Regency;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class MarketBusinessExportJob implements ShouldQueue
{
    use Queueable;

    public $organizationId;
    public $marketId;
    public $uuid;
    public $role;
    /**
     * Create a new job instance.
     */
    public function __construct($organizationId, $marketId, $uuid, $role)
    {
        $this->organizationId = $organizationId;
        $this->uuid = $uuid;
        $this->marketId = $marketId;
        $this->role = $role;

        AssignmentStatus::find($this->uuid)->update(['status' => 'loading',]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $status = AssignmentStatus::find($this->uuid);
            $status->update(['status' => 'loading']);

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
            $market = Market::find($this->marketId);
            $business = MarketBusiness::query();

            if ($this->role == 'adminprov') {
                if ($this->organizationId != null) {
                    $business->whereHas('market', function ($query) {
                        $query->where('organization_id', $this->organizationId);
                    });
                }
            } else if ($this->role == 'adminkab') {
                $business->whereHas('market', function ($query) use ($status) {
                    $query->where('organization_id', $status->user->organization_id);
                });
            } else if ($this->role == 'pml' || $this->role == 'operator') {
                $marketIds = $status->user->markets->pluck('id');
                $business->whereIn('market_id', $marketIds);
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
