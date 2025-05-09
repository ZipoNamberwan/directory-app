<?php

namespace App\Jobs;

use App\Models\AssignmentStatus;
use App\Models\Market;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class MarketMasterExportJob implements ShouldQueue
{
    use Queueable;

    public $organizationId;
    public $uuid;

    public function __construct($uuid, $organizationId)
    {
        $this->organizationId = $organizationId;
        $this->uuid = $uuid;

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

            if (!Storage::exists('market_business_master')) {
                Storage::makeDirectory('market_business_master');
            }

            $stream = fopen(Storage::path('/market_business_master/' . $this->uuid . ".csv"), 'w+');

            $csv = Writer::createFromStream($stream);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');

            $csv->insertOne([
                'id',
                'Nama_Sentra_Ekonomi',
                'Kabupaten',
                'Kecamatan',
                'Desa',
                'Dikerjakan_Oleh',
                'Status_Penyelesaian',
                'Status_Target',
                'Tipe',
            ]);

            $markets = Market::query()
                ->where('organization_id', $this->organizationId);

            $markets
                ->with(['marketType', 'regency', 'subdistrict', 'village', 'organization'])
                ->chunk(1000, function ($businesses) use ($csv) {
                    foreach ($businesses as $row) {
                        $csv->insertOne([
                            $row->id,
                            $row->name,
                            '[' . $row->regency->id . '] ' . $row->regency->name,
                            '[' . $row->subdistrict->short_code . '] ' . $row->subdistrict->name,
                            '[' . $row->village->short_code . '] ' . $row->village->name,
                            '[' . $row->organization->id . '] ' . $row->organization->name,
                            Market::getTransformedCompletionStatusByValue($row->completion_status),
                            $row->targetCategory,
                            $row->marketType->name,
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
