<?php

namespace App\Jobs;

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
            $idArea = $record[0];
            $email = $record[4];

            if (Str::startsWith($idArea, $this->regency)) {
                if ($idArea && $email) {
                    $id_user = User::where('email', $email)->first();
                    if ($id_user) {
                        $updates[$idArea] = $id_user->id;
                    } else {
                        if (!in_array($email, $invalid_emails)) {
                            $invalid_emails[] = $email;
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

                $query = "UPDATE categorized_business SET pcl_id = CASE sls_id";

                $bindings = [];
                foreach ($updates as $idArea => $idUser) {
                    $query .= " WHEN ? THEN ?";
                    $bindings[] = $idArea;
                    $bindings[] = $idUser;
                }

                $query .= " END WHERE sls_id IN (" . implode(',', array_fill(0, count($updates), '?')) . ")";
                $bindings = array_merge($bindings, array_keys($updates));

                DB::statement($query, $bindings);
            });
        }
    }
}
