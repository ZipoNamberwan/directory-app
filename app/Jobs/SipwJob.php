<?php

namespace App\Jobs;

use App\Helpers\DatabaseSelector;
use App\Models\FailedBusiness;
use App\Models\Sls;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SipwJob implements ShouldQueue
{
    use Queueable;

    public $records;

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
        foreach ($this->records as $record) {
            try {
                if ($record['petugas_email'] != null && $record['assign_status'] == 'Sudah Assign') {
                    // Process the record
                    $email = $record['petugas_email'];
                    $idsls = $record['idsls'] . '00';

                    if (User::where('email', $email)->exists() && Sls::where('id', $idsls)->exists()) {
                        $user = User::where('email', $email)->first();
                        $sls = Sls::where('id', $idsls)->first();

                        $dataWithId = [
                            'user_id' => $user->id,
                            'sls_id' => $sls->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        foreach (DatabaseSelector::getListConnections() as $connection) {
                            DB::connection($connection)->table('sls_user_wilkerstat')->insert($dataWithId);
                        }
                    } else {
                        throw new Exception("User or SLS not found for email: {$email} and SLS ID: {$idsls}");
                    }
                }
            } catch (Exception $e) {
                FailedBusiness::create(['record' => json_encode($record)]);
            }
        }
    }
}
