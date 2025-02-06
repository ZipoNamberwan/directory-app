<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ReportJob;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $datetime->modify('+7 hours');
        $today = $datetime->format('Y-m-d');

        foreach (['village', 'subdistrict', 'regency'] as $level) {

            $levelTable = ($level === 'regency') ? 'regencies' : $level . 's';

            DB::table('report_' . $level)->where('date', $today)->delete();

            $insertData = [];

            foreach (['sls', 'non_sls'] as $type) {
                $typeBusinessTable = $type . '_business';

                $reports = DB::table($levelTable)
                    ->leftJoin($typeBusinessTable, $levelTable . '.id', '=', $typeBusinessTable . '.' . $level . '_id')
                    ->select(
                        $levelTable . '.id',
                        DB::raw('COUNT(CASE WHEN status_id = 1 THEN 1 END) AS not_update'),
                        DB::raw('COUNT(CASE WHEN status_id = 2 THEN 1 END) AS exist'),
                        DB::raw('COUNT(CASE WHEN status_id = 3 THEN 1 END) AS not_exist'),
                        DB::raw('COUNT(CASE WHEN status_id = 4 THEN 1 END) AS not_scope'),
                        DB::raw('COUNT(CASE WHEN status_id = 90 THEN 1 END) AS new'),
                        // DB::raw('COUNT(*) AS total'),
                    )
                    ->groupBy($levelTable . '.id')
                    ->get();

                foreach ($reports as $report) {
                    $insertData[] = [
                        'id' => (string) Str::uuid(),
                        'not_update' => $report->not_update,
                        'exist' => $report->exist,
                        'not_exist' => $report->not_exist,
                        'not_scope' => $report->not_scope,
                        'new' => $report->new,
                        // 'total' => $report->total,
                        $level . '_id' => $report->id,
                        'date' => $today,
                        'type' => $type
                    ];
                }
            }

            // Bulk insert logic
            $batchSize = 1000;
            foreach (array_chunk($insertData, $batchSize) as $chunk) {
                ReportJob::dispatch($level, $chunk);
            }
        }
    }
}
