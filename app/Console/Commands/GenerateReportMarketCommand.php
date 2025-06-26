<?php

namespace App\Console\Commands;

use App\Models\ReportMarketBusinessMarket;
use App\Models\ReportMarketBusinessRegency;
use App\Models\ReportMarketBusinessUser;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class GenerateReportMarketCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-report-market';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $datetime = new DateTime();
        // $datetime->modify('+7 hours');
        $today = $datetime->format('Y-m-d');
        $now = now();

        $businessCountByOrganizationAndMarketType = DB::table('organizations')
            ->crossJoin('market_types')
            ->leftJoin('markets', function ($join) {
                $join->on('markets.organization_id', '=', 'organizations.id')
                    ->on('markets.market_type_id', '=', 'market_types.id');
            })
            ->leftJoin('market_business', 'markets.id', '=', 'market_business.market_id')
            ->select(
                'organizations.id as organization_id',
                'market_types.id as market_type_id',
                DB::raw('COUNT(DISTINCT market_business.id) as total_business'),
                DB::raw('COUNT(DISTINCT markets.id) as total_market'),
                DB::raw('COUNT(DISTINCT CASE WHEN market_business.id IS NOT NULL THEN markets.id END) as market_have_business'),

                // Target category counts
                DB::raw("COUNT(DISTINCT CASE WHEN markets.target_category = 'target' THEN markets.id END) as target"),
                DB::raw("COUNT(DISTINCT CASE WHEN markets.target_category = 'non target' THEN markets.id END) as non_target"),

                // Completion status counts (only for target markets)
                DB::raw("COUNT(DISTINCT CASE WHEN markets.target_category = 'target' AND markets.completion_status = 'not start' THEN markets.id END) as not_start"),
                DB::raw("COUNT(DISTINCT CASE WHEN markets.target_category = 'target' AND markets.completion_status = 'on going' THEN markets.id END) as on_going"),
                DB::raw("COUNT(DISTINCT CASE WHEN markets.target_category = 'target' AND markets.completion_status = 'done' THEN markets.id END) as done")
            )
            ->groupBy('organizations.id', 'market_types.id')
            ->orderBy('organizations.id')
            ->orderBy('market_types.id')
            ->get();

        ReportMarketBusinessRegency::where('date', $today)->delete();

        // Step 1: Prepare data for bulk insert
        $reportData = [];

        foreach ($businessCountByOrganizationAndMarketType as $regency) {
            $reportData[] = [
                'id' => (string) Str::uuid(),
                'uploaded' => $regency->total_business,
                'total_market' => $regency->total_market,
                'market_have_business' => $regency->market_have_business,
                'target' => $regency->target,
                'non_target' => $regency->non_target,
                'not_start' => $regency->not_start,
                'on_going' => $regency->on_going,
                'done' => $regency->done,
                'organization_id' => $regency->organization_id,
                'date' => $today,
                'created_at' => $now,
                'updated_at' => $now,
                'market_type_id' => $regency->market_type_id,
            ];
        }

        // Step 2: Bulk insert
        ReportMarketBusinessRegency::insert($reportData);

        $pml = Role::where('name', 'pml')->value('id');
        $operator = Role::where('name', 'operator')->value('id');
        $adminkab = Role::where('name', 'adminkab')->value('id');
        $adminprov = Role::where('name', 'adminprov')->value('id');

        $businessCountByUser = DB::table('users')
            ->leftJoin('market_business', 'users.id', '=', 'market_business.user_id')
            ->join('model_has_roles', function ($join) use ($pml, $operator, $adminkab, $adminprov) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', \App\Models\User::class)
                    ->whereIn('model_has_roles.role_id', [$pml, $operator, $adminkab, $adminprov]);
            })
            ->select(
                'users.id as user_id',
                'users.organization_id',
                DB::raw('COUNT(market_business.id) as total')
            )
            ->groupBy('users.id', 'users.organization_id')
            ->orderBy('users.id')
            ->get();

        ReportMarketBusinessUser::where('date', $today)->delete();

        $reportUserData = [];

        foreach ($businessCountByUser as $user) {
            $reportUserData[] = [
                'id' => (string) Str::uuid(),
                'uploaded' => $user->total,
                'user_id' => $user->user_id,
                'organization_id' => $user->organization_id,
                'date' => $today,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        foreach (array_chunk($reportUserData, 1000) as $chunk) {
            ReportMarketBusinessUser::insert($chunk);
        }

        $businessCountByMarket = DB::table('markets')
            ->leftJoin('market_business', 'markets.id', '=', 'market_business.market_id')
            ->select(
                'markets.id as market_id',
                'markets.organization_id',
                'markets.completion_status',
                'markets.target_category',
                'markets.market_type_id',
                DB::raw('COUNT(market_business.id) as total')
            )
            ->groupBy('markets.id', 'markets.organization_id')
            ->orderBy('markets.id')
            ->get();

        ReportMarketBusinessMarket::where('date', $today)->delete();

        $reportMarketData = [];

        foreach ($businessCountByMarket as $market) {
            $reportMarketData[] = [
                'id' => (string) Str::uuid(),
                'uploaded' => $market->total,
                'market_id' => $market->market_id,
                'organization_id' => $market->organization_id,
                'completion_status' => $market->completion_status,
                'target_category' => $market->target_category,
                'market_type_id' => $market->market_type_id,
                'date' => $today,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        foreach (array_chunk($reportMarketData, 1000) as $chunk) {
            ReportMarketBusinessMarket::insert($chunk);
        }


        // Generate report for supplement
        // Delete existing reports for today
        DB::table('report_supplement')
            ->whereDate('date', $today)
            ->delete();

        $businessCounts = DB::table('supplement_business')
            ->join('projects', 'supplement_business.project_id', '=', 'projects.id')
            ->whereNull('supplement_business.deleted_at') // ✅ Exclude soft-deleted rows
            ->select(
                'projects.type',
                'supplement_business.organization_id',
                DB::raw('COUNT(*) as uploaded')
            )
            ->groupBy('projects.type', 'supplement_business.organization_id')
            ->get()
            ->keyBy(fn($item) => $item->type . '|' . $item->organization_id);
        $rows = [];

        $organizationIds = DB::table('organizations')
            ->pluck('id')
            ->toArray();

        $types = [
            'swmaps supplement',
            'kendedes mobile',
            'swmaps market',
        ];

        foreach ($types as $type) {
            foreach ($organizationIds as $orgId) {
                $key = $type . '|' . $orgId;
                $uploaded = $businessCounts[$key]->uploaded ?? 0;

                $rows[] = [
                    'id' => Str::uuid(),
                    'uploaded' => $uploaded,
                    'type' => $type,
                    'organization_id' => $orgId,
                    'date' => $today,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('report_supplement')->insert($rows);
    }
}
