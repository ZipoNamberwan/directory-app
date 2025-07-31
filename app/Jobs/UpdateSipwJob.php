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
use Illuminate\Support\Facades\Log;

class UpdateSipwJob implements ShouldQueue
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

                    if (!User::where('email', $email)->exists()) {
                        $u = User::create(['firstname' => $record['petugas_nama'], 'email' => $email, 'regency_id' => substr($record['idsls'], 0, 4), 'organization_id' => substr($record['idsls'], 0, 4), 'username' => $email, 'password' => Hash::make('se26sukses'), 'is_wilkerstat_user' => true]);
                        $u->assignRoleAllDatabase('pcl');
                    }

                    if (User::where('email', $email)->exists() && Sls::where('id', $idsls)->exists()) {
                        $assignment = DB::table('sls_user_wilkerstat')->where('sls_id', $idsls)->get();
                        if ($assignment->isEmpty()) {
                            //create new assignment
                            $user = User::where('email', $email)->first();

                            $dataWithId = [
                                'user_id' => $user->id,
                                'sls_id' => $idsls,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            foreach (DatabaseSelector::getListConnections() as $connection) {
                                Log::info($connection);
                                DB::connection($connection)->table('sls_user_wilkerstat')->insert($dataWithId);
                            }
                        } else {
                            //update existing assignment
                            $user = User::where('email', $email)->first();
                            if ($assignment->first()->user_id != $user->id) {
                                foreach (DatabaseSelector::getListConnections() as $connection) {
                                    DB::connection($connection)->table('sls_user_wilkerstat')->where('sls_id', $idsls)->update(['user_id' => $user->id]);
                                }
                            }
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
