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

        $businessCountByOrganization = DB::table('organizations')
            ->leftJoin('markets', 'organizations.id', '=', 'markets.organization_id')
            ->leftJoin('market_business', 'markets.id', '=', 'market_business.market_id')
            ->select(
                'organizations.id as organization_id',
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
            ->groupBy('organizations.id')
            ->orderBy('organizations.id')
            ->get();

        ReportMarketBusinessRegency::where('date', $today)->delete();

        // Step 1: Prepare data for bulk insert
        $reportData = [];

        foreach ($businessCountByOrganization as $regency) {
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
                'updated_at' => $now
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

        ReportMarketBusinessUser::insert($reportUserData);

        $businessCountByMarket = DB::table('markets')
            ->leftJoin('market_business', 'markets.id', '=', 'market_business.market_id')
            ->select(
                'markets.id as market_id',
                'markets.organization_id',
                'markets.completion_status',
                'markets.target_category',
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
                'date' => $today,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        ReportMarketBusinessMarket::insert($reportMarketData);
    }
}
