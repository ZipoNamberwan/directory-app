<?php

namespace App\Jobs;

use App\Helpers\DatabaseSelector;
use App\Models\AssignmentStatus;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ImportAssignmentJob implements ShouldQueue
{
    use Queueable;

    protected $records;
    protected $uuid;
    protected $regency;
    /**
     * Create a new job instance.
     */
    public function __construct($records, $regency, $uuid)
    {
        $this->records = $records;
        $this->uuid = $uuid;
        $this->regency = $regency;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $updates = [];

        $invalid_emails = [];

        foreach ($this->records as $record) {
            if (Str::startsWith($record[0], $this->regency)) {
                if ($record[0] && $record[4] && $record[5]) {

                    $pml = User::where(['email' => $record[4]])->first();
                    $pcl = User::where(['email' => $record[5]])->first();

                    if ($pml != null && $pcl != null) {
                        $updates[] = [
                            'sls_id' => $record[0],
                            'pml_id' => $pml->id,
                            'pcl_id' => $pcl->id,
                        ];
                    } else {
                        foreach ([$record[4], $record[5]] as $em) {
                            if (!in_array($em, $invalid_emails)) {
                                $invalid_emails[] = $em;
                            }
                        }
                    }
                }
            }
        }

        if (count($invalid_emails) > 0) {
            $message = "Email tidak ditemukan: " . implode(', ', $invalid_emails) . "<br>";
            $assignmentStatus = AssignmentStatus::find($this->uuid);
            $assignmentStatus->update([
                'message' => $assignmentStatus->message . $message,
            ]);
            return;
        }

        if (count($updates) > 0) {
            DB::transaction(function () use ($updates) {

                $query = "UPDATE sls_business SET 
                            pcl_id = CASE sls_id";

                $bindings = [];

                foreach ($updates as $update) {
                    $query .= " WHEN ? THEN ?";
                    $bindings[] = $update['sls_id'];
                    $bindings[] = $update['pcl_id'];
                }

                $query .= " END, pml_id = CASE sls_id";

                foreach ($updates as $update) {
                    $query .= " WHEN ? THEN ?";
                    $bindings[] = $update['sls_id'];
                    $bindings[] = $update['pml_id'];
                }

                $query .= " END WHERE sls_id IN (" . implode(',', array_fill(0, count($updates), '?')) . ")";

                foreach ($updates as $update) {
                    $bindings[] = $update['sls_id'];
                }

                DB::on(DatabaseSelector::getConnection($this->regency))->statement($query, $bindings);
            });

            // DB::transaction(function () use ($updates) {

            //     $query = "UPDATE non_sls_business SET 
            //                 pml_id = CASE sls_id";

            //     $bindings = [];

            //     foreach ($updates as $update) {
            //         $query .= " WHEN ? THEN ?";
            //         $bindings[] = $update['sls_id'];
            //         $bindings[] = $update['pml_id'];
            //     }

            //     $query .= " END WHERE sls_id IN (" . implode(',', array_fill(0, count($updates), '?')) . ")";

            //     foreach ($updates as $update) {
            //         $bindings[] = $update['sls_id'];
            //     }

            //     DB::statement($query, $bindings);
            // });
        }
    }
}
