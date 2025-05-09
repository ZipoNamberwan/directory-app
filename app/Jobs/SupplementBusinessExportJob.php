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
                'Kabupaten',
            ]);

            $business = null;
            $business = SupplementBusiness::query();

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
                            "[" . $row->organization->long_code . "] " . $row->organization->name,
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
