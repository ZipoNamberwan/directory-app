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

        $businessCountByRegency = DB::table('regencies')
            ->leftJoin('market_business', 'regencies.id', '=', 'market_business.regency_id')
            ->leftJoin('markets', 'regencies.id', '=', 'markets.regency_id')
            ->select(
                'regencies.id as regency_id',
                DB::raw('COUNT(DISTINCT market_business.id) as total_business'),
                DB::raw('COUNT(DISTINCT markets.id) as total_market')
            )
            ->groupBy('regencies.id')
            ->orderBy('regencies.id')
            ->get();

        ReportMarketBusinessRegency::where('date', $today)->delete();

        // Step 1: Prepare data for bulk insert
        $reportData = [];

        foreach ($businessCountByRegency as $regency) {
            $reportData[] = [
                'id' => (string) Str::uuid(),
                'uploaded' => $regency->total_business,
                'total_market' => $regency->total_market,
                'regency_id' => $regency->regency_id,
                'date' => $today,
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
                'users.regency_id',
                DB::raw('COUNT(market_business.id) as total')
            )
            ->groupBy('users.id', 'users.regency_id')
            ->orderBy('users.id')
            ->get();

        ReportMarketBusinessUser::where('date', $today)->delete();

        $reportUserData = [];

        foreach ($businessCountByUser as $user) {
            $reportUserData[] = [
                'id' => (string) Str::uuid(),
                'uploaded' => $user->total,
                'user_id' => $user->user_id,
                'regency_id' => $user->regency_id,
                'date' => $today,
            ];
        }

        ReportMarketBusinessUser::insert($reportUserData);

        $businessCountByMarket = DB::table('markets')
            ->leftJoin('market_business', 'markets.id', '=', 'market_business.market_id')
            ->select('markets.id as market_id', 'markets.regency_id', DB::raw('COUNT(market_business.id) as total'))
            ->groupBy('markets.id', 'markets.regency_id')
            ->orderBy('markets.id')
            ->get();

        ReportMarketBusinessMarket::where('date', $today)->delete();

        $reportMarketData = [];

        foreach ($businessCountByMarket as $market) {
            $reportMarketData[] = [
                'id' => (string) Str::uuid(),
                'uploaded' => $market->total,
                'market_id' => $market->market_id,
                'regency_id' => $market->regency_id,
                'date' => $today,
            ];
        }

        ReportMarketBusinessMarket::insert($reportMarketData);
    }
}
