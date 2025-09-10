<?php

namespace App\Jobs;

use App\Models\AssignmentStatus;
use App\Models\AnomalyRepair;
use App\Models\SupplementBusiness;
use App\Models\MarketBusiness;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class AnomalyExportJob implements ShouldQueue
{
    use Queueable;
    public $timeout = 0;

    public $uuid;
    public $organizationId;

    /**
     * Create a new job instance.
     */
    public function __construct($organizationId, $uuid)
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

            $user = $status->user;

            if (!Storage::exists('anomaly')) {
                Storage::makeDirectory('anomaly');
            }

            $stream = fopen(Storage::path('/anomaly/' . $this->uuid . ".csv"), 'w+');

            $csv = Writer::createFromStream($stream);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');

            $csv->insertOne([
                'Business_ID',
                'Nama_Usaha',
                'Status_Bangunan',
                'Alamat',
                'Deskripsi',
                'Sektor',
                'Catatan',
                'Latitude',
                'Longitude',
                'User_Upload',
                'User_Email',
                'Tipe_Usaha',
                'Satker',
                'Kabupaten',
                'Kecamatan',
                'Desa',
                'SLS',
                'Ditagging_pada',
                'Anomaly_Type',
                'Anomaly_Name',
                'Old_Value',
                'Fixed_Value',
                'Note',
                'Repaired_At',
                'Repaired_By_Name',
                'Repaired_By_Email'
            ]);

            $anomalies = AnomalyRepair::query();

            // if ($user->hasRole('adminkab')) {
            //     $anomalies->where(function ($query) use ($status) {
            //         $query->where(function ($subQuery) use ($status) {
            //             // For SupplementBusiness
            //             $subQuery->where('business_type', 'App\\Models\\SupplementBusiness')
            //                 ->whereIn('business_id', function ($businessQuery) use ($status) {
            //                     $businessQuery->select('id')
            //                         ->from('supplement_business')
            //                         ->where(function ($orgQuery) use ($status) {
            //                             $orgQuery->where('organization_id', $status->user->organization_id);
            //                         });
            //                 });
            //         })->orWhere(function ($subQuery) use ($status) {
            //             // For MarketBusiness
            //             $subQuery->where('business_type', 'App\\Models\\MarketBusiness')
            //                 ->whereIn('business_id', function ($businessQuery) use ($status) {
            //                     $businessQuery->select('id')
            //                         ->from('market_business')
            //                         ->where(function ($orgQuery) use ($status) {
            //                             $orgQuery->where('organization_id', $status->user->organization_id);
            //                         });
            //                 });
            //         });
            //     });
            // } else if ($user->hasRole('pml') || $user->hasRole('operator') || $user->hasRole('pcl')) {
            //     $anomalies->where(function ($query) use ($status) {
            //         $query->where(function ($subQuery) use ($status) {
            //             // For SupplementBusiness
            //             $subQuery->where('business_type', 'App\\Models\\SupplementBusiness')
            //                 ->whereIn('business_id', function ($businessQuery) use ($status) {
            //                     $businessQuery->select('id')
            //                         ->from('supplement_business')
            //                         ->where('user_id', $status->user_id);
            //                 });
            //         })->orWhere(function ($subQuery) use ($status) {
            //             // For MarketBusiness
            //             $subQuery->where('business_type', 'App\\Models\\MarketBusiness')
            //                 ->whereIn('business_id', function ($businessQuery) use ($status) {
            //                     $businessQuery->select('id')
            //                         ->from('market_business')
            //                         ->where('user_id', $status->user_id);
            //                 });
            //         });
            //     });
            // }

            $anomalies
                ->with([
                    'anomalyType:id,code,name',
                    'lastRepairedBy:id,firstname,email'
                ])
                ->orderBy('business_id')
                ->orderBy('created_at')
                ->chunk(1000, function ($anomalyRecords) use ($csv) {
                    foreach ($anomalyRecords as $anomaly) {
                        // Get business data based on business type
                        $business = null;
                        $businessType = '';

                        if ($anomaly->business_type === 'App\\Models\\SupplementBusiness') {
                            $business = SupplementBusiness::with([
                                'organization',
                                'user',
                                'regency',
                                'subdistrict',
                                'village',
                                'sls'
                            ])->find($anomaly->business_id);
                            $businessType = 'Suplemen';
                        } else if ($anomaly->business_type === 'App\\Models\\MarketBusiness') {
                            $business = MarketBusiness::with([
                                'market.organization',
                                'user',
                                'regency',
                                'subdistrict',
                                'village',
                                'sls'
                            ])->find($anomaly->business_id);
                            $businessType = 'Sentra Ekonomi';
                        }

                        if ($business) {
                            $organization = null;
                            if ($anomaly->business_type === 'App\\Models\\SupplementBusiness') {
                                $organization = $business->organization;
                            } else if ($anomaly->business_type === 'App\\Models\\MarketBusiness') {
                                $organization = $business->market->organization ?? null;
                            }

                            $csv->insertOne([
                                $business->id,
                                $business->name,
                                $business->status,
                                $business->address,
                                $business->description,
                                $business->sector,
                                $business->note,
                                $business->latitude,
                                $business->longitude,
                                $business->user->firstname ?? '',
                                $business->user->email ?? '',
                                $businessType,
                                $organization ? "[" . $organization->long_code . "] " . $organization->name : '',
                                $business->regency ? "[" . $business->regency->long_code . "] " . $business->regency->name : '',
                                $business->subdistrict ? "[" . $business->subdistrict->short_code . "] " . $business->subdistrict->name : '',
                                $business->village ? "[" . $business->village->short_code . "] " . $business->village->name : '',
                                $business->sls ? "[" . $business->sls->short_code . "] " . $business->sls->name : '',
                                $business->created_at->format('d-m-Y H:i:s'),
                                $anomaly->anomalyType->code ?? '',
                                $anomaly->anomalyType->name ?? '',
                                $anomaly->old_value ?? '',
                                $anomaly->fixed_value ?? '',
                                $anomaly->note ?? '',
                                $anomaly->repaired_at ? $anomaly->repaired_at->format('d-m-Y H:i:s') : '',
                                $anomaly->lastRepairedBy?->firstname ?? '',
                                $anomaly->lastRepairedBy?->email ?? '',
                            ]);
                        }
                    }
                });

            fclose($stream);

            AssignmentStatus::find($this->uuid)->update(['status' => 'success']);
        } catch (Exception $e) {
            AssignmentStatus::find($this->uuid)->update(['status' => 'failed', 'message' => $e->getMessage()]);
        }
    }
}
