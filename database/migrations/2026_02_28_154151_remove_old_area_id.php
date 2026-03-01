<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FK matrix
     * table => [ fk_column => master_table ]
     *
     * This is kept in sync with:
     * - 2026_01_01_042709_rename_old_fk_column_and_rename_uuid_to_id_step_5
     */
    private array $map = [

        // =========================
        // BUSINESS TABLES
        // =========================
        'market_business' => [
            'regency_id'     => 'regencies',
            'subdistrict_id' => 'subdistricts',
            'village_id'     => 'villages',
            'sls_id'         => 'sls',
        ],

        'supplement_business' => [
            'regency_id'     => 'regencies',
            'subdistrict_id' => 'subdistricts',
            'village_id'     => 'villages',
            'sls_id'         => 'sls',
        ],

        'sls_business' => [
            'regency_id'     => 'regencies',
            'subdistrict_id' => 'subdistricts',
            'village_id'     => 'villages',
            'sls_id'         => 'sls',
        ],

        'non_sls_business' => [
            'regency_id'     => 'regencies',
            'subdistrict_id' => 'subdistricts',
            'village_id'     => 'villages',
            'sls_id'         => 'sls',
        ],

        // =========================
        // MARKET & STATUS
        // =========================
        'markets' => [
            'regency_id'     => 'regencies',
            'subdistrict_id' => 'subdistricts',
            'village_id'     => 'villages',
        ],

        'market_upload_status' => [
            'regency_id' => 'regencies',
        ],

        'supplement_upload_status' => [
            'regency_id'     => 'regencies',
            'subdistrict_id' => 'subdistricts',
            'village_id'     => 'villages',
            'sls_id'         => 'sls',
        ],

        // =========================
        // REPORT TABLES
        // =========================
        'report_regency' => [
            'regency_id' => 'regencies',
        ],

        'report_subdistrict' => [
            'subdistrict_id' => 'subdistricts',
        ],

        'report_village' => [
            'village_id' => 'villages',
        ],

        'report_sls' => [
            'sls_id' => 'sls',
        ],

        // =========================
        // MASTER TABLES (hierarchical)
        // =========================
        'subdistricts' => [
            'regency_id' => 'regencies',
        ],

        'villages' => [
            'subdistrict_id' => 'subdistricts',
        ],

        'sls' => [
            'village_id' => 'villages',
        ],

        // =========================
        // SURVEY
        // =========================
        'survey_business' => [
            'regency_id'     => 'regencies',
            'subdistrict_id' => 'subdistricts',
            'village_id'     => 'villages',
            'sls_id'         => 'sls',
        ],

        // =========================
        // USER CONTEXT (REVISED)
        // =========================
        'user_acting_contexts' => [
            'acting_reg_id' => ['table' => 'regencies', 'uuid_column' => 'regency_uuid'],
        ],

        'users' => [
            'regency_id' => 'regencies',
        ],

        // =========================
        // SLS RELATED
        // =========================
        'sls_update_prelist' => [
            'sls_id' => 'sls',
        ],

        'sls_user_wilkerstat' => [
            'sls_id' => 'sls',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->map as $table => $relations) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($relations as $fkColumn => $masterTableConfig) {
                $oldFkColumn = $fkColumn . '_old';

                if (!Schema::hasColumn($table, $oldFkColumn)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $tableBlueprint) use ($oldFkColumn) {
                    $tableBlueprint->dropColumn($oldFkColumn);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->map as $table => $relations) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($relations as $fkColumn => $masterTableConfig) {
                $oldFkColumn = $fkColumn . '_old';

                if (Schema::hasColumn($table, $oldFkColumn)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $tableBlueprint) use ($oldFkColumn) {
                    // Step 5 ended with *_id_old columns being nullable strings.
                    $tableBlueprint->string($oldFkColumn)->nullable();
                });
            }
        }
    }
};
