<?php

namespace App\Jobs;

use App\Models\AssignmentStatus;
use App\Models\NonSlsBusiness;
use App\Models\SlsBusiness;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class BusinessExportJob implements ShouldQueue
{
    use Queueable;

    public $regencyId;
    public $uuid;
    public $type;
    /**
     * Create a new job instance.
     */
    public function __construct($regencyId, $uuid, $type)
    {
        $this->regencyId = $regencyId;
        $this->uuid = $uuid;
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        AssignmentStatus::find($this->uuid)->update(['status' => 'loading',]);

        $stream = fopen(Storage::path($this->uuid . ".csv"), 'w+');

        // Create CSV writer
        $csv = Writer::createFromStream($stream);
        $csv->setDelimiter(',');
        $csv->setEnclosure('"');

        if ($this->type == 'download-sls-business') {
            $csv->insertOne([
                'id',
                'Nama_Kabupaten',
                'Nama_Kecamatan',
                'Nama_Desa',
                'Nama_SLS',
                'Nama_Usaha',
                'Nama_Pemilik',
                'Sumber',
                'Status',
                'PCL',
            ]);

            SlsBusiness::where(['regency_id' => $this->regencyId])
                ->with(['regency', 'subdistrict', 'village', 'sls', 'pcl', 'status'])
                ->chunk(1000, function ($businesses) use ($csv) {
                    foreach ($businesses as $row) {
                        $csv->insertOne([
                            $row->id,
                            "[" . $row->regency->short_code . "] " .  $row->regency->name,
                            "[" . $row->subdistrict->short_code . "] " .  $row->subdistrict->name,
                            "[" . $row->village->short_code . "] " .  $row->village->name,
                            "[" . $row->sls->short_code . "] " .  $row->sls->name,
                            $row->name,
                            $row->owner,
                            $row->source,
                            $row->status->name,
                            optional($row->pcl)->firstname,
                        ]);
                    }
                });

            fclose($stream);
        } else if ($this->type == 'download-non-sls-business') {
            $csv->insertOne([
                'id',
                'Nama_Kabupaten',
                'Nama_Kecamatan',
                'Nama_Desa',
                'Nama_SLS',
                'Nama_Usaha',
                'Nama_Pemilik',
                'Kategori',
                'KBLI',
                'Sumber',
                'Status',
                'PCL',
            ]);

            NonSlsBusiness::where(['regency_id' => $this->regencyId])
                ->with(['regency', 'subdistrict', 'village', 'sls', 'pcl', 'status'])
                ->chunk(1000, function ($businesses) use ($csv) {
                    foreach ($businesses as $row) {
                        $csv->insertOne([
                            $row->id,
                            "[" . $row->regency->short_code . "] " .  $row->regency->name,
                            $row->subdistrict != null ? ("[" . $row->subdistrict->short_code . "] " .  $row->subdistrict->name) : null,
                            $row->village != null ? ("[" . $row->village->short_code . "] " .  $row->village->name) : null,
                            $row->sls != null ? ("[" . $row->sls->short_code . "] " .  $row->sls->name) : null,
                            $row->name,
                            $row->owner,
                            $row->category,
                            $row->kbli,
                            $row->source,
                            $row->status->name,
                            $row->pcl != null ? $row->pcl->firstname : null,
                        ]);
                    }
                });
        }

        AssignmentStatus::find($this->uuid)->update(['status' => 'success',]);
    }
}
