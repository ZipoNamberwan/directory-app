<?php

namespace App\Jobs;

use App\Exports\SlsAssignmentExport;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SlsAssignmentExportJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $regency;
    /**
     * Create a new job instance.
     */
    public function __construct($regency)
    {
        $this->regency = $regency;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        (new SlsAssignmentExport($this->regency))->store($this->regency . '.xlsx');
    }
}
