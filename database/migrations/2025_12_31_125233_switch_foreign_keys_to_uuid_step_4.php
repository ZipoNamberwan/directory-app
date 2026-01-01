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
     * Switch FK → UUID
     */
    public function up(): void
    {
        // First, ensure UUID columns in master tables have unique indexes
        $masterTables = ['regencies', 'subdistricts', 'villages', 'sls'];
        foreach ($masterTables as $masterTable) {
            try {
                Schema::table($masterTable, function (Blueprint $table) {
                    $table->unique('uuid');
                });
            } catch (\Throwable $e) {
                // ignore if already exists
            }
        }

        // Now add foreign key constraints
        foreach ($this->map as $table => $relations) {
            foreach ($relations as $fkColumn => $masterTableConfig) {
                // Support both string format and array format
                if (is_array($masterTableConfig)) {
                    $masterTable = $masterTableConfig['table'];
                    $uuidColumn = $masterTableConfig['uuid_column'] ?? str_replace('_id', '_uuid', $fkColumn);
                } else {
                    $masterTable = $masterTableConfig;
                    $uuidColumn = str_replace('_id', '_uuid', $fkColumn);
                }

                // Drop old FK (wrapped in try-catch to handle already dropped FKs)
                try {
                    Schema::table($table, function (Blueprint $tableBlueprint) use ($fkColumn) {
                        $tableBlueprint->dropForeign([$fkColumn]);
                    });
                } catch (\Throwable $e) {
                    // ignore if not exists
                }

                // Add UUID FK
                Schema::table($table, function (Blueprint $tableBlueprint) use ($uuidColumn, $masterTable) {
                    $tableBlueprint->foreign($uuidColumn)
                        ->references('uuid')
                        ->on($masterTable)
                        ->onUpdate('cascade')
                        ->onDelete('restrict');
                });
            }
        }
    }

    /**
     * Rollback: restore FK → legacy ID
     */
    public function down(): void
    {
        foreach ($this->map as $table => $relations) {
            foreach ($relations as $fkColumn => $masterTableConfig) {
                // Support both string format and array format
                if (is_array($masterTableConfig)) {
                    $masterTable = $masterTableConfig['table'];
                    $uuidColumn = $masterTableConfig['uuid_column'] ?? str_replace('_id', '_uuid', $fkColumn);
                } else {
                    $masterTable = $masterTableConfig;
                    $uuidColumn = str_replace('_id', '_uuid', $fkColumn);
                }

                // Drop UUID FK (wrapped in try-catch to handle already dropped FKs)
                try {
                    Schema::table($table, function (Blueprint $tableBlueprint) use ($uuidColumn) {
                        $tableBlueprint->dropForeign([$uuidColumn]);
                    });
                } catch (\Throwable $e) {
                    // ignore if not exists
                }

                // Restore legacy FK
                Schema::table($table, function (Blueprint $tableBlueprint) use ($fkColumn, $masterTable) {
                    $tableBlueprint->foreign($fkColumn)
                        ->references('id')
                        ->on($masterTable)
                        ->onUpdate('cascade')
                        ->onDelete('restrict');
                });
            }
        }

        // Finally, drop unique indexes from master tables
        $masterTables = ['regencies', 'subdistricts', 'villages', 'sls'];
        foreach ($masterTables as $masterTable) {
            try {
                Schema::table($masterTable, function (Blueprint $table) {
                    $table->dropUnique(["uuid"]);
                });
            } catch (\Throwable $e) {
                // ignore if doesn't exist
            }
        }
    }
};
