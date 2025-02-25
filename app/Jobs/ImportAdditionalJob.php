<?php

namespace App\Jobs;

use App\Models\AssignmentStatus;
use App\Models\NonSlsBusiness;
use App\Models\Sls;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class ImportAdditionalJob implements ShouldQueue
{
    use Queueable;

    protected $records;
    protected $uuid;
    protected $regency;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($records, $regency, $uuid, $userId)
    {
        $this->records = $records;
        $this->uuid = $uuid;
        $this->regency = $regency;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $errors = [];
        $rowNumber = 1;

        foreach ($this->records as $record) {
            $rowErrors = [];

            for ($i = 0; $i <= 4; $i++) {
                if (empty($record[$i])) {
                    $rowErrors[] = "Kolom " . ($i + 1) . " kosong pada baris $rowNumber.";
                }
            }

            if (!empty($record[0])) {
                if (!str_starts_with($record[0], $this->regency)) {
                    $rowErrors[] = "Id SLS pada baris $rowNumber harus dimulai dengan kode wilayah " . $this->regency;
                }

                if (Sls::find($record[0]) === null) {
                    $rowErrors[] = "Id SLS pada baris $rowNumber tidak ditemukan dalam master.";
                }
            }

            if (!empty($rowErrors)) {
                $errors[$rowNumber] = $rowErrors;
            } else {
                NonSlsBusiness::create([
                    'regency_id' => substr($record[0], 0, 4),
                    'subdistrict_id' => substr($record[0], 0, 7),
                    'village_id' => substr($record[0], 0, 10),
                    'sls_id' => $record[0],
                    'name' => $record[1],
                    'owner' => $record[2],
                    'address' => $record[3],
                    'source' => $record[4],
                    'is_new' => true,
                    'status_id' => 90,
                    'level' => 'village',
                    'last_modified_by' => $this->userId,
                ]);
            }

            $rowNumber++;
        }

        if (count($errors) > 0) {
            $assignmentStatus = AssignmentStatus::find($this->uuid);
            $errorMessages = [];
            foreach ($errors as $row => $messages) {
                foreach ($messages as $message) {
                    $errorMessages[] = $message;
                }
            }
            $assignmentStatus->update([
                'message' => $assignmentStatus->message . implode("<br>", $errorMessages) . "<br>",
            ]);
        }
    }
}
