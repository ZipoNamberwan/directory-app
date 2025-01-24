<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ImportAssignmentJob implements ShouldQueue
{
    use Queueable;

    protected $records;
    /**
     * Create a new job instance.
     */
    public function __construct($records)
    {
        $this->records = $records;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $updates = [];
        foreach ($this->records as $record) {
            $idArea = $record[0];
            $email = $record[4];

            if ($idArea && $email) {
                $id_user = User::where('email', $email)->first();
                if ($id_user) {
                    $updates[$idArea] = $id_user->id;
                }
            }
        }

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
