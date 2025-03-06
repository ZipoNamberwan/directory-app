<?php

namespace App\Console\Commands;

use App\Helpers\DatabaseSelector;
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

        foreach (['village', 'subdistrict', 'regency', 'province'] as $level) {

            $levelTable = ($level === 'regency') ? 'regencies' : ($level !== 'province' ? $level . 's' : null);

            DB::table('report_' . $level)->where('date', $today)->delete();

            $insertData = [];

            foreach (['sls', 'non_sls'] as $type) {

                foreach (DatabaseSelector::getListConnections() as $connection) {
                    $typeBusinessTable = $type . '_business';

                    if ($level !== 'province') {
                        $query = DB::connection($connection)->table($levelTable)->leftJoin($typeBusinessTable, $levelTable . '.id', '=', $typeBusinessTable . '.' . $level . '_id');
                    } else {
                        $query = DB::connection($connection)->table($typeBusinessTable);
                    }

                    $query->whereIn($typeBusinessTable . '.regency_id', DatabaseSelector::getRegenciesForConnection($connection));

                    $query->select([
                        DB::raw($level !== 'province' ? "$levelTable.id AS id" : "NULL AS id"),
                        DB::raw('COUNT(CASE WHEN status_id = 1 THEN 1 END) AS not_update'),
                        DB::raw('COUNT(CASE WHEN status_id = 2 THEN 1 END) AS exist'),
                        DB::raw('COUNT(CASE WHEN status_id = 3 THEN 1 END) AS not_exist'),
                        DB::raw('COUNT(CASE WHEN status_id = 4 THEN 1 END) AS not_scope'),
                        DB::raw('COUNT(CASE WHEN status_id = 90 THEN 1 END) AS new')
                    ]);
                    if ($level !== 'province') {
                        $query->groupBy($levelTable . ".id");
                    }

                    $reports = $query->get();

                    foreach ($reports as $report) {
                        $dt =  [
                            'id' => (string) Str::uuid(),
                            'not_update' => $report->not_update,
                            'exist' => $report->exist,
                            'not_exist' => $report->not_exist,
                            'not_scope' => $report->not_scope,
                            'new' => $report->new,
                            'date' => $today,
                            'type' => $type
                        ];
                        if ($level !== 'province') {
                            $dt[$level . '_id'] =  $report->id;
                        }

                        $insertData[] = $dt;
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
