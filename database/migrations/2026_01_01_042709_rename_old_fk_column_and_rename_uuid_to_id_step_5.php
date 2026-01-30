<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FK matrix
     * table => [ fk_column => master_table ]
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
     * Rename old FK columns to *_id_old and rename UUID columns to ID
     */
    public function up(): void
    {
        foreach ($this->map as $table => $relations) {
            foreach ($relations as $fkColumn => $masterTableConfig) {
                // Support both string format and array format
                if (is_array($masterTableConfig)) {
                    $uuidColumn = $masterTableConfig['uuid_column'] ?? str_replace('_id', '_uuid', $fkColumn);
                } else {
                    $uuidColumn = str_replace('_id', '_uuid', $fkColumn);
                }

                $oldFkColumn = $fkColumn . '_old';

                Schema::table($table, function (Blueprint $tableBlueprint) use ($fkColumn, $oldFkColumn) {
                    // Rename old FK column to *_id_old
                    $tableBlueprint->renameColumn($fkColumn, $oldFkColumn);
                });

                Schema::table($table, function (Blueprint $tableBlueprint) use ($oldFkColumn) {
                    // Make *_id_old nullable
                    $tableBlueprint->string($oldFkColumn)->nullable()->change();
                });

                Schema::table($table, function (Blueprint $tableBlueprint) use ($uuidColumn, $fkColumn) {
                    // Rename UUID column to ID column
                    $tableBlueprint->renameColumn($uuidColumn, $fkColumn);
                });
            }
        }
    }

    /**
     * Reverse: rename ID back to UUID and restore old FK columns
     */
    public function down(): void
    {
        foreach ($this->map as $table => $relations) {
            foreach ($relations as $fkColumn => $masterTableConfig) {
                // Support both string format and array format
                if (is_array($masterTableConfig)) {
                    $uuidColumn = $masterTableConfig['uuid_column'] ?? str_replace('_id', '_uuid', $fkColumn);
                } else {
                    $uuidColumn = str_replace('_id', '_uuid', $fkColumn);
                }

                $oldFkColumn = $fkColumn . '_old';

                Schema::table($table, function (Blueprint $tableBlueprint) use ($fkColumn, $uuidColumn, $oldFkColumn) {
                    // Rename ID column back to UUID
                    $tableBlueprint->renameColumn($fkColumn, $uuidColumn);
                    
                    // Rename *_id_old back to original FK column name
                    $tableBlueprint->renameColumn($oldFkColumn, $fkColumn);
                });
            }
        }
    }
};
