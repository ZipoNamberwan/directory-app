<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ReportJob implements ShouldQueue
{
    use Queueable;

    public $level;
    public $records;

    /**
     * Create a new job instance.
     */
    public function __construct($level, $records)
    {
        $this->level = $level;
        $this->records = $records;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::table('report_' . $this->level)->insert($this->records);
    }
}
