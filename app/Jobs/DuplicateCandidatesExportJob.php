<?php

namespace App\Jobs;

use App\Models\AssignmentStatus;
use App\Models\DuplicateCandidate;
use App\Models\MarketBusiness;
use App\Models\SupplementBusiness;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class DuplicateCandidatesExportJob implements ShouldQueue
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

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $status = AssignmentStatus::find($this->uuid);
            $status->update(['status' => 'loading']);

            $user = $status->user;

            if (!Storage::exists('duplicate')) {
                Storage::makeDirectory('duplicate');
            }

            $stream = fopen(Storage::path('/duplicate/' . $this->uuid . ".csv"), 'w+');

            $csv = Writer::createFromStream($stream);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');

            $csv->insertOne([
                'Nama Usaha A',
                'Nama Usaha B',
                'Nama Pemilik Usaha A',
                'Nama Pemilik Usaha B',
                'Satker Usaha A',
                'Satker Usaha B',
                'Jarak',
                'Status Perbaikan',
                'Dikonfirmasi Terakhir Oleh',
                'Dikonfirmasi Terakhir Oleh Email',
                'SLS Usaha A',
                'SLS Usaha B',
            ]);

            $candidates = DuplicateCandidate::query();
            if ($user->hasRole('adminprov')) {
                // no filter
            } else if ($user->hasRole('adminkab')) {
                $candidates->where(function ($q) use ($user) {
                    $q->where('center_business_organization_id', $user->organization_id)
                        ->orWhere('nearby_business_organization_id', $user->organization_id);
                });
            } else {
                $candidates->where('id', 'not possible');
            }

            $chunkCount = 0;
            $candidates
                ->with([
                    'lastConfirmedBy:id,firstname,email',
                    'centerBusiness' => function ($morphTo) {
                        $morphTo->constrain([
                            SupplementBusiness::class => function ($query) {
                                $query->withTrashed()->with([
                                    'sls'
                                ]);
                            },
                            MarketBusiness::class => function ($query) {
                                $query->withTrashed()->with([
                                    'sls'
                                ]);
                            },
                        ]);
                    },
                    'nearbyBusiness' => function ($morphTo) {
                        $morphTo->constrain([
                            SupplementBusiness::class => function ($query) {
                                $query->withTrashed()->with([
                                    'sls'
                                ]);
                            },
                            MarketBusiness::class => function ($query) {
                                $query->withTrashed()->with([
                                    'sls'
                                ]);
                            },
                        ]);
                    }
                ])
                ->orderBy('created_at')
                ->chunk(1000, function ($candidateRecords) use ($csv, &$chunkCount) {
                    $chunkCount++;

                    foreach ($candidateRecords as $candidate) {

                        $status = 'Unknown';
                        if ($candidate->status === 'notconfirmed') {
                            $status = 'Belum Dikonfirmasi';
                        } elseif ($candidate->status === 'keep1') {
                            $status = 'Keep Usaha A';
                        } elseif ($candidate->status === 'keep2') {
                            $status = 'Keep Usaha B';
                        } elseif ($candidate->status === 'keepall') {
                            $status = 'Keep Kedua Usaha';
                        } elseif ($candidate->status === 'deleteall') {
                            $status = 'Hapus Kedua Usaha';
                        }

                        $csv->insertOne([
                            $candidate->center_business_name,
                            $candidate->nearby_business_name,
                            $candidate->center_business_owner,
                            $candidate->nearby_business_owner,

                            $candidate->center_business_organization_id,
                            $candidate->nearby_business_organization_id,
                            $candidate->distance_meters,
                            $status,
                            $candidate->lastConfirmedBy?->firstname ?? '',
                            $candidate->lastConfirmedBy?->email ?? '',
                            $candidate->centerBusiness?->sls?->long_code ?? '',
                            $candidate->nearbyBusiness?->sls?->long_code ?? '',
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
