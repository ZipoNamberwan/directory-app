<?php

namespace App\Jobs;

use App\Models\AssignmentStatus;
use App\Models\SupplementBusiness;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class SupplementBusinessExportJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 0;

    public $organizationId;
    public $uuid;
    public $role;
    /**
     * Create a new job instance.
     */
    public function __construct($organizationId, $uuid, $role)
    {
        $this->organizationId = $organizationId;
        $this->uuid = $uuid;
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

            if (!Storage::exists('supplement')) {
                Storage::makeDirectory('supplement');
            }

            $stream = fopen(Storage::path('/supplement/' . $this->uuid . ".csv"), 'w+');

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
                'User_Upload',
                'User_Email',
                'Tipe',
                'Satker',
                'Kabupaten',
                'Kecamatan',
                'Desa',
                'SLS',
                'Ditagging pada'
            ]);

            $business = null;
            $business = SupplementBusiness::query();

            if ($this->role == 'adminkab') {
                $business->where(function ($query) use ($status) {
                    $query->where('organization_id', $status->user->organization_id)
                        ->orWhere('regency_id', $status->user->organization_id);
                });
            } else if ($this->role == 'pml' || $this->role == 'operator' || $this->role == 'pcl') {
                $business->where('user_id', $status->user_id);
            }

            $business
                ->with(['organization', 'user'])
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
                            $row->user->firstname,
                            $row->user->email,
                            $row->project->type,
                            $row->organization != null ? "[" . $row->organization->long_code . "] " . $row->organization->name : null,
                            $row->regency != null ? "[" . $row->regency->long_code . "] " . $row->regency->name : null,
                            $row->subdistrict != null ? "[" . $row->subdistrict->short_code . "] " . $row->subdistrict->name : null,
                            $row->village != null ? "[" . $row->village->short_code . "] " . $row->village->name : null,
                            $row->sls != null ? "[" . $row->sls->short_code . "] " . $row->sls->name : null,
                            $row->created_at->format('d-m-Y H:i:s'),
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
