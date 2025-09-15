<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ReportJob;
use App\Models\MarketBusiness;
use App\Models\SupplementBusiness;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DateTime;

class GenerateReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Village Report, Subdistrict Report, and Regency Report';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $datetime = new DateTime();
        // $datetime->modify('+7 hours');
        $today    = $datetime->format('Y-m-d');

        foreach (['sls', 'village', 'subdistrict', 'regency', 'province'] as $level) {

            DB::table('report_' . $level)
                ->whereDate('created_at', $today)
                ->delete();

            $timestamp  = now();
            $insertData = [];

            // define models and their business_type
            $sources = [
                ['model' => SupplementBusiness::query(), 'type' => 'supplement'],
                ['model' => MarketBusiness::query(),     'type' => 'market'],
            ];

            foreach ($sources as $source) {
                $query = $source['model'];
                $type  = $source['type'];

                if ($level === 'province') {
                    // province = no groupBy
                    $counts = $query->select(DB::raw('COUNT(*) as total'))->get();

                    foreach ($counts as $row) {
                        $insertData[] = [
                            'id'            => Str::uuid(),
                            'business_type' => $type,
                            'total'         => $row->total,
                            'created_at'    => $timestamp,
                            'updated_at'    => $timestamp,
                        ];
                    }
                } else {
                    // group by chosen level
                    $column = $level . '_id';
                    $counts = $query->select($column, DB::raw('COUNT(*) as total'))
                        ->groupBy($column)
                        ->get();

                    foreach ($counts as $row) {
                        $insertData[] = [
                            'id'            => Str::uuid(),
                            $column         => $row->{$column},   // dynamic property
                            'business_type' => $type,
                            'total'         => $row->total,
                            'created_at'    => $timestamp,
                            'updated_at'    => $timestamp,
                        ];
                    }
                }
            }

            $batchSize = 1000;
            foreach (array_chunk($insertData, $batchSize) as $chunk) {
                ReportJob::dispatch($level, $chunk);
            }
        }
    }
}
