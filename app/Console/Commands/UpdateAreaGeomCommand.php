<?php

namespace App\Console\Commands;

use App\Helpers\DatabaseSelector;
use App\Jobs\UpdateAreaGeomJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateAreaGeomCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-area-geom {--table=} {--chunk=1000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to (re)populate the geom column of regencies, subdistricts, villages and sls from their geojson files';

    /**
     * Table name => geojson folder name mapping.
     *
     * @var array
     */
    protected $tables = [
        'regencies' => 'regency',
        'subdistricts' => 'subdistrict',
        'villages' => 'village',
        'sls' => 'sls_by_subdistrict',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chunk = (int) $this->option('chunk');
        $onlyTable = $this->option('table');

        $tables = $this->tables;
        if ($onlyTable) {
            if (!isset($tables[$onlyTable])) {
                $this->error("Unknown table '{$onlyTable}'. Available tables: " . implode(', ', array_keys($this->tables)));
                return Command::FAILURE;
            }
            $tables = [$onlyTable => $tables[$onlyTable]];
        }

        foreach (DatabaseSelector::getListConnections() as $connection) {
            $this->info("🔄 Processing connection: {$connection}");

            $periodVersions = DB::connection($connection)
                ->table('area_periods')
                ->pluck('period_version', 'id')
                ->all();

            foreach ($tables as $table => $areaType) {
                $dispatched = 0;

                DB::connection($connection)
                    ->table($table)
                    ->whereNull('geom')
                    ->whereNotNull('long_code')
                    ->whereNotNull('area_period_id')
                    ->chunkById($chunk, function ($rows) use ($connection, $table, $areaType, $periodVersions, &$dispatched) {
                        UpdateAreaGeomJob::dispatch($connection, $table, $areaType, $periodVersions, $rows->all());
                        $dispatched += $rows->count();
                    });

                $this->info("  ➜ {$table}: dispatched {$dispatched} row(s) across " . ceil($dispatched / max($chunk, 1)) . " job(s)");
            }
        }

        $this->info('✅ All geom update jobs dispatched. Monitor with: php artisan queue:work');

        return Command::SUCCESS;
    }
}
