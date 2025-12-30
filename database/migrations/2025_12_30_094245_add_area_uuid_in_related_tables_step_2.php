<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->addUuid('regency_uuid', $this->regencyTables());
        $this->addUuid('subdistrict_uuid', $this->subdistrictTables());
        $this->addUuid('village_uuid', $this->villageTables());
        $this->addUuid('sls_uuid', $this->slsTables());
    }

    private function addUuid(string $column, array $tables): void
    {
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($column) {
                if (! Schema::hasColumn($tableBlueprint->getTable(), $column)) {
                    $tableBlueprint->uuid($column)->nullable()->after(str_replace('_uuid', '_id', $column));
                }
            });
        }
    }

    private function regencyTables(): array
    {
        return [
            'market_business',
            'supplement_business',
            'sls_business',
            'non_sls_business',
            'markets',
            'market_upload_status',
            'report_regency',
            'subdistricts',
            'supplement_upload_status',
            'survey_business',
            'user_acting_context',
            'users',
        ];
    }

    private function subdistrictTables(): array
    {
        return [
            'market_business',
            'supplement_business',
            'sls_business',
            'non_sls_business',
            'markets',
            'report_subdistrict',
            'villages',
            'supplement_upload_status',
            'survey_business',
        ];
    }

    private function villageTables(): array
    {
        return [
            'market_business',
            'supplement_business',
            'sls_business',
            'non_sls_business',
            'markets',
            'report_village',
            'sls',
            'supplement_upload_status',
            'survey_business',
        ];
    }

    private function slsTables(): array
    {
        return [
            'market_business',
            'supplement_business',
            'sls_business',
            'non_sls_business',
            'report_sls',
            'supplement_upload_status',
            'survey_business',
            'sls_update_prelist',
            'sls_user_wilkerstat',
        ];
    }
};
