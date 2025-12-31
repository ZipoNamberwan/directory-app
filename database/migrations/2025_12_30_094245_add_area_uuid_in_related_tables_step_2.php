<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        foreach ($this->uuidMap() as $uuidColumn => $config) {
            $this->addUuidColumn(
                $uuidColumn,
                $config['tables'],
                $config['default_after']
            );
        }
    }

    public function down(): void
    {
        foreach ($this->uuidMap() as $uuidColumn => $config) {
            foreach ($config['tables'] as $key => $value) {
                $table = is_array($value) ? $key : $value;

                if (Schema::hasColumn($table, $uuidColumn)) {
                    Schema::table($table, function (Blueprint $tableBlueprint) use ($uuidColumn) {
                        $tableBlueprint->dropColumn($uuidColumn);
                    });
                }
            }
        }
    }

    /**
     * ======================================================
     * UUID COLUMN MAP (Single Source of Truth)
     * ======================================================
     */
    private function uuidMap(): array
    {
        return [

            'regency_uuid' => [
                'default_after' => 'regency_id',
                'tables' => [
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
                    'users',

                    // 👇 special case handled declaratively
                    'user_acting_contexts' => [
                        'after' => 'acting_reg_id',
                    ],
                ],
            ],

            'subdistrict_uuid' => [
                'default_after' => 'subdistrict_id',
                'tables' => [
                    'market_business',
                    'supplement_business',
                    'sls_business',
                    'non_sls_business',
                    'markets',
                    'report_subdistrict',
                    'villages',
                    'supplement_upload_status',
                    'survey_business',
                ],
            ],

            'village_uuid' => [
                'default_after' => 'village_id',
                'tables' => [
                    'market_business',
                    'supplement_business',
                    'sls_business',
                    'non_sls_business',
                    'markets',
                    'report_village',
                    'sls',
                    'supplement_upload_status',
                    'survey_business',
                ],
            ],

            'sls_uuid' => [
                'default_after' => 'sls_id',
                'tables' => [
                    'market_business',
                    'supplement_business',
                    'sls_business',
                    'non_sls_business',
                    'report_sls',
                    'supplement_upload_status',
                    'survey_business',
                    'sls_update_prelist',
                    'sls_user_wilkerstat',
                ],
            ],
        ];
    }

    /**
     * ======================================================
     * ADD UUID COLUMN (Generic + Safe)
     * ======================================================
     */
    private function addUuidColumn(
        string $column,
        array $tables,
        string $defaultAfter
    ): void {
        foreach ($tables as $key => $value) {

            if (is_array($value)) {
                $table = $key;
                $after = $value['after'];
            } else {
                $table = $value;
                $after = $defaultAfter;
            }

            Schema::table($table, function (Blueprint $tableBlueprint) use ($column, $after) {
                if (!Schema::hasColumn($tableBlueprint->getTable(), $column)) {
                    $tableBlueprint->uuid($column)->nullable()->after($after);
                }
            });
        }
    }
};
