<?php

namespace App\Console\Commands;

use App\Models\ReportMarketBusinessMarket;
use App\Models\ReportMarketBusinessRegency;
use App\Models\ReportMarketBusinessUser;
use App\Models\ReportSupplementBusinessRegency;
use App\Models\ReportSupplementBusinessUser;
use App\Models\ReportTotalBusinessRegency;
use App\Models\ReportTotalBusinessUser;
use App\Models\User;
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

        // START OF REPORT MARKET BUSINESS BY REGENCY
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
        ReportMarketBusinessRegency::insert($reportData);
        // === END OF REPORT MARKET BUSINESS BY REGENCY ===

        // START OF REPORT MARKET BUSINESS BY USER
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
        // === END OF REPORT MARKET BUSINESS BY USER ===


        // START OF REPORT MARKET BUSINESS BY MARKET
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
        // === END OF REPORT MARKET BUSINESS BY MARKET ===

        // START OF REPORT SUPPLEMENT BUSINESS BY REGENCY ===
        ReportSupplementBusinessRegency::whereDate('date', $today)
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

        ReportSupplementBusinessRegency::insert($rows);
        // === END OF REPORT SUPPLEMENT BUSINESS BY REGENCY ===


        // START OF REPORT SUPPLEMENT BUSINESS BY USER ===
        ReportSupplementBusinessUser::whereDate('date', $today)
            ->delete();

        $reportData = DB::table('users')
            ->leftJoin('supplement_business', function ($join) {
                $join->on('users.id', '=', 'supplement_business.user_id')
                    ->whereNull('supplement_business.deleted_at');
            })
            ->select(
                'users.id as user_id',
                'users.organization_id',
                DB::raw('COUNT(supplement_business.id) as uploaded')
            )
            ->groupBy(
                'users.id',
                'users.organization_id'
            )
            ->get();

        $insertData = [];

        foreach ($reportData as $row) {
            $insertData[] = [
                'id' => Str::uuid()->toString(),
                'uploaded' => $row->uploaded,
                'user_id' => $row->user_id,
                'organization_id' => $row->organization_id,
                'date' => $today,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($insertData)) {
            collect($insertData)->chunk(1000)->each(function ($chunk) {
                ReportSupplementBusinessUser::insert($chunk->toArray());
            });
        }
        // END OF REPORT SUPPLEMENT BUSINESS BY USER ===

        // START OF REPORT TOTAL BUSINESS BY USER ===
        ReportTotalBusinessUser::whereDate('date', $today)
            ->delete();

        $supplements = ReportSupplementBusinessUser::query()
            ->where('date', $today)
            ->select('user_id', DB::raw('SUM(uploaded) as supplement'))
            ->groupBy('user_id')
            ->pluck('supplement', 'user_id');

        $markets = ReportMarketBusinessUser::query()
            ->where('date', $today)
            ->select('user_id', DB::raw('SUM(uploaded) as market'))
            ->groupBy('user_id')
            ->pluck('market', 'user_id');

        $userIds = $supplements->keys()->merge($markets->keys())->unique();

        $userOrgs = User::whereIn('id', $userIds)
            ->pluck('organization_id', 'id'); // [user_id => organization_id]

        $insertData = [];

        foreach ($userIds as $userId) {
            $insertData[] = [
                'id' => Str::uuid()->toString(),
                'user_id' => $userId,
                'organization_id' => $userOrgs->get($userId),
                'market' => $markets->get($userId, 0),
                'supplement' => $supplements->get($userId, 0),
                'total' => ($markets->get($userId, 0) + $supplements->get($userId, 0)),
                'date' => $today,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        collect($insertData)->chunk(1000)->each(function ($chunk) {
            ReportTotalBusinessUser::insert($chunk->toArray());
        });

        // END OF REPORT TOTAL BUSINESS BY USER ===

        // START OF REPORT TOTAL BUSINESS BY REGENCY ===

        ReportTotalBusinessRegency::whereDate('date', $today)
            ->delete();

        $marketReports = ReportMarketBusinessRegency::select('organization_id', DB::raw('SUM(uploaded) as market_uploaded'))
            ->where('date', $today)
            ->groupBy('organization_id')
            ->get()
            ->keyBy('organization_id'); // ✅ this is the fix

        $supplementReports = ReportSupplementBusinessRegency::select('organization_id', DB::raw('SUM(uploaded) as supplement_uploaded'))
            ->where('date', $today)
            ->groupBy('organization_id')
            ->get()
            ->keyBy('organization_id'); // ✅ fix here too

        $combined = collect();

        // Now this will be an array of actual UUIDs or organization IDs
        $allOrganizationIds = $marketReports->keys()->merge($supplementReports->keys())->unique();
        foreach ($allOrganizationIds as $orgId) {
            $market = $marketReports[$orgId]->market_uploaded ?? 0;
            $supplement = $supplementReports[$orgId]->supplement_uploaded ?? 0;

            $combined->push([
                'id' => Str::uuid()->toString(),
                'organization_id' => $orgId,
                'market' => $market,
                'supplement' => $supplement,
                'total' => $market + $supplement,
                'date' => $today,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        ReportTotalBusinessRegency::insert($combined->toArray());

        // END OF REPORT TOTAL BUSINESS BY REGENCY ===
    }
}
