<?php

namespace Database\Seeders;

use App\Helpers\DatabaseSelector;
use App\Models\Project;
use App\Models\SupplementBusiness;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = [
            [
                'id' => Str::uuid(),
                'name' => 'SWMAPS Sentra Ekonomi',
                'type' => 'swmaps market',
            ],
            [
                'id' => Str::uuid(),
                'name' => 'SWMAPS Supplement',
                'type' => 'swmaps supplement',
            ],
        ];

        foreach (DatabaseSelector::getListConnections() as $connection) {
            foreach ($projects as $project) {
                Project::on($connection)->create($project);
            }
        }

        $project = Project::where('type', 'swmaps supplement')->first();
        foreach (DatabaseSelector::getListConnections() as $connection) {
            SupplementBusiness::query()->each(function ($business) use ($project) {
                $business->update([
                    'project_id' => $project != null ? $project->id : null,
                ]);
            });
        }
    }
}
