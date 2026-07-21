<?php

namespace App\Console\Commands;

use App\Helpers\DatabaseSelector;
use App\Models\AreaPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateNewAreaPeriodCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-new-area-period';

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
        $latestVersion = AreaPeriod::max('period_version');

        $data = [];
        $data['id'] = Str::uuid()->toString();
        $data['period_version'] = $latestVersion + 1;
        $data['name'] = 'Semester 1 2025 Sub SLS';
        $data['is_active'] = false;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        foreach (DatabaseSelector::getListConnections() as $connection) {
            AreaPeriod::on($connection)->insert($data);
        }
    }
}
