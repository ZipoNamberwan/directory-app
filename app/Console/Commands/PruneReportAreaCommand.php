<?php

namespace App\Console\Commands;

use App\Models\ReportProvince;
use App\Models\ReportRegency;
use App\Models\ReportSubdistrict;
use App\Models\ReportVillage;
use Illuminate\Console\Command;

class PruneReportAreaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prune-report-area';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune report area command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ReportProvince::truncate();
        ReportRegency::truncate();
        ReportSubdistrict::truncate();
        ReportVillage::truncate();
    }
}
