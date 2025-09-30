<?php

namespace App\Jobs;

use App\Models\AssignmentStatus;
use App\Models\Market;
use App\Models\MarketBusiness;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class MarketBusinessExportJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 0;

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
                'Satker',
                'User_Upload',
                'User_Email',
                'Kabupaten',
                'Kecamatan',
                'Desa',
                'SLS'
            ]);

            $business = null;
            $market = Market::find($this->marketId);
            $business = MarketBusiness::query();

            if ($this->role == 'adminkab') {
                $business->where(function ($q) use ($status) {
                    $q->whereHas('market', function ($query) use ($status) {
                        $query->where('organization_id', $status->user->organization_id);
                    })->orWhere('regency_id', $status->user->organization_id);
                });
            } else if ($this->role == 'pml' || $this->role == 'operator') {
                $business->where('user_id', $status->user_id);
            }

            if ($market) {
                $business->where('market_id', $this->marketId);
            }

            $business
                ->with(['market', 'market.organization', 'user', 'regency', 'subdistrict', 'village', 'sls'])
                ->chunk(1000, function ($businesses) use ($csv) {
                    foreach ($businesses as $row) {
                        $csv->insertOne([
                            $row->id,
                            $this->cleanCsvValue($row->name),
                            $this->cleanCsvValue($row->status),
                            $this->cleanCsvValue($row->address),
                            $this->cleanCsvValue($row->description),
                            $this->cleanCsvValue($row->sector),
                            $this->cleanCsvValue($row->note),
                            $row->latitude,
                            $row->longitude,
                            $row->market->name,
                            $row->market != null ? "[" . $row->market->organization->long_code . "] " . $row->market->organization->name : null,
                            $row->user->firstname,
                            $row->user->email,
                            $row->regency != null ? "[" . $row->regency->long_code . "] " . $row->regency->name : null,
                            $row->subdistrict != null ? "[" . $row->subdistrict->short_code . "] " . $row->subdistrict->name : null,
                            $row->village != null ? "[" . $row->village->short_code . "] " . $row->village->name : null,
                            $row->sls != null ? "[" . $row->sls->short_code . "] " . $row->sls->name : null,
                        ]);
                    }
                });

            fclose($stream);

            AssignmentStatus::find($this->uuid)->update(['status' => 'success']);
        } catch (Exception $e) {
            AssignmentStatus::find($this->uuid)->update(['status' => 'failed', 'message' => $e->getMessage()]);
        }
    }

    private function cleanCsvValue($value) {
        if ($value === null) {
            return '';
        }
        // normalize line breaks and tabs
        $value = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $value);
        return trim($value);
    }
}
