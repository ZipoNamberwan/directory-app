<?php

namespace App\Console\Commands;

use App\Models\ReportMarketBusinessMarket;
use App\Models\ReportMarketBusinessRegency;
use App\Models\ReportMarketBusinessUser;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        $businessCountByRegency = DB::table('market_business')
            ->select('regency_id', DB::raw('COUNT(*) as total'))
            ->groupBy('regency_id')
            ->orderByDesc('total')
            ->get();

        ReportMarketBusinessRegency::where('date', $today)->delete();

        foreach ($businessCountByRegency as $regency) {
            ReportMarketBusinessRegency::create([
                'id' => Str::uuid(),
                'uploaded' => $regency->total,
                'regency_id' => $regency->regency_id,
                'date' => $today,
            ]);
        }

        $businessCountByUser = DB::table('market_business')
            ->select('user_id', DB::raw('COUNT(*) as total'))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->get();

        ReportMarketBusinessUser::where('date', $today)->delete();

        foreach ($businessCountByUser as $user) {
            ReportMarketBusinessUser::create([
                'id' => Str::uuid(),
                'uploaded' => $user->total,
                'user_id' => $user->user_id,
                'date' => $today,
            ]);
        }

        $businessCountByMarket = DB::table('market_business')
            ->select('market_id', DB::raw('COUNT(*) as total'))
            ->groupBy('market_id')
            ->orderByDesc('total')
            ->get();

        ReportMarketBusinessMarket::where('date', $today)->delete();

        foreach ($businessCountByMarket as $market) {
            ReportMarketBusinessMarket::create([
                'id' => Str::uuid(),
                'uploaded' => $market->total,
                'market_id' => $market->market_id,
                'date' => $today,
            ]);
        }
    }
}
