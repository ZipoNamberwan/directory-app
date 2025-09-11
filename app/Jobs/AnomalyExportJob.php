<?php

namespace App\Jobs;

use App\Models\AssignmentStatus;
use App\Models\AnomalyRepair;
use App\Models\SupplementBusiness;
use App\Models\MarketBusiness;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use Throwable;

class AnomalyExportJob implements ShouldQueue
{
    use Queueable;
    public $timeout = 0;

    public $uuid;
    public $organizationId;

    public function __construct($organizationId, $uuid)
    {
        $this->organizationId = $organizationId;
        $this->uuid = $uuid;

        AssignmentStatus::find($this->uuid)->update(['status' => 'loading']);
    }

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
                'Nama_Usaha',
                'Pemilik',
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
                'Tipe_Anomali',
                'Nama_Anomali',
                'Status_Anomali',
                'Nilai_Lama',
                'Nilai_Perbaikan (Jika diperbaiki)',
                'Catatan_Anomali (Jika Diabaikan)',
                'Diperbaiki_pada',
                'Diperbaiki_Oleh',
                'Diperbaiki_Email',
            ]);

            $anomalies = AnomalyRepair::query();

            if ($user->hasRole('adminkab')) {
                $organizationId = $status->user->organization_id;

                $anomalies->where(function ($query) use ($organizationId) {
                    // SupplementBusiness with direct organization_id
                    $query->where(function ($subQuery) use ($organizationId) {
                        $subQuery->where('business_type', 'App\\Models\\SupplementBusiness')
                            ->whereIn(
                                'business_id',
                                DB::table('supplement_business')
                                    ->select('id')
                                    ->where('organization_id', $organizationId)
                            );
                    })
                        // MarketBusiness with organization_id through markets
                        ->orWhere(function ($subQuery) use ($organizationId) {
                            $subQuery->where('business_type', 'App\\Models\\MarketBusiness')
                                ->whereIn(
                                    'business_id',
                                    DB::table('market_business')
                                        ->select('market_business.id')
                                        ->join('markets', 'market_business.market_id', '=', 'markets.id')
                                        ->where('markets.organization_id', $organizationId)
                                );
                        });
                });
            }

            $chunkCount = 0;
            $anomalies
                ->with([
                    'anomalyType:id,code,name',
                    'lastRepairedBy:id,firstname,email',
                    'business' => function ($morphTo) {
                        $morphTo->constrain([
                            SupplementBusiness::class => function ($query) {
                                $query->withTrashed()->with([
                                    'organization',
                                    'user',
                                    'regency',
                                    'subdistrict',
                                    'village',
                                    'sls'
                                ]);
                            },
                            MarketBusiness::class => function ($query) {
                                $query->withTrashed()->with([
                                    'market.organization',
                                    'user',
                                    'regency',
                                    'subdistrict',
                                    'village',
                                    'sls'
                                ]);
                            },
                        ]);
                    }
                ])
                ->orderBy('business_id')
                ->orderBy('created_at')
                ->chunk(1000, function ($anomalyRecords) use ($csv, &$chunkCount) {
                    $chunkCount++;
                    $chunkStart = microtime(true);

                    foreach ($anomalyRecords as $anomaly) {
                        $business = $anomaly->business;
                        if (!$business) {
                            continue; // skip if missing
                        }

                        $businessType = $anomaly->business_type === SupplementBusiness::class
                            ? 'Suplemen'
                            : 'Sentra Ekonomi';

                        $organization = null;
                        if ($business instanceof SupplementBusiness) {
                            $organization = $business->organization;
                        } elseif ($business instanceof MarketBusiness) {
                            $organization = $business->market->organization ?? null;
                        }

                        $status = 'Unknown';
                        if ($anomaly->status === 'notconfirmed') {
                            $status = 'Belum Dikonfirmasi';
                        } elseif ($anomaly->status === 'fixed') {
                            $status = 'Sudah Diperbaiki';
                        } elseif ($anomaly->status === 'dismissed') {
                            $status = 'Diabaikan';
                        } elseif ($anomaly->status === 'deleted') {
                            $status = 'Dihapus';
                        } elseif ($anomaly->status === 'moved') {
                            $status = 'Dipindahkan';
                        }

                        $csv->insertOne([
                            $business->name,
                            $business->owner,
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
                            $status,
                            $anomaly->old_value ?? '',
                            $anomaly->fixed_value ?? '',
                            $anomaly->note ?? '',
                            $anomaly->repaired_at ? $anomaly->repaired_at->format('d-m-Y H:i:s') : '',
                            $anomaly->lastRepairedBy?->firstname ?? '',
                            $anomaly->lastRepairedBy?->email ?? '',
                        ]);
                    }

                    $chunkTime = microtime(true) - $chunkStart;
                });

            fclose($stream);

            AssignmentStatus::find($this->uuid)->update(['status' => 'success']);
        } catch (Exception $e) {
            AssignmentStatus::find($this->uuid)->update(['status' => 'failed', 'message' => $e->getMessage()]);
        }
    }

    public function failed(Throwable $exception): void
    {
        AssignmentStatus::find($this->uuid)?->update([
            'status'  => 'failed',
            'message' => $exception->getMessage(),
        ]);
    }
}
