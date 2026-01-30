<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tune based on DB power
     */
    private int $batchSize = 10000;

    public function up(): void
    {
        /**
         * FK → UUID MATRIX
         * table => [
         *   fk_column => [
         *     'master' => master_table,
         *     'uuid'   => uuid_column_on_table
         *   ]
         * ]
         */
        $map = [

            // =========================
            // BUSINESS TABLES
            // =========================
            'market_business' => [
                'regency_id'     => ['master' => 'regencies',     'uuid' => 'regency_uuid'],
                'subdistrict_id' => ['master' => 'subdistricts',  'uuid' => 'subdistrict_uuid'],
                'village_id'     => ['master' => 'villages',      'uuid' => 'village_uuid'],
                'sls_id'         => ['master' => 'sls',           'uuid' => 'sls_uuid'],
            ],

            'supplement_business' => [
                'regency_id'     => ['master' => 'regencies',     'uuid' => 'regency_uuid'],
                'subdistrict_id' => ['master' => 'subdistricts',  'uuid' => 'subdistrict_uuid'],
                'village_id'     => ['master' => 'villages',      'uuid' => 'village_uuid'],
                'sls_id'         => ['master' => 'sls',           'uuid' => 'sls_uuid'],
            ],

            'sls_business' => [
                'regency_id'     => ['master' => 'regencies',     'uuid' => 'regency_uuid'],
                'subdistrict_id' => ['master' => 'subdistricts',  'uuid' => 'subdistrict_uuid'],
                'village_id'     => ['master' => 'villages',      'uuid' => 'village_uuid'],
                'sls_id'         => ['master' => 'sls',           'uuid' => 'sls_uuid'],
            ],

            'non_sls_business' => [
                'regency_id'     => ['master' => 'regencies',     'uuid' => 'regency_uuid'],
                'subdistrict_id' => ['master' => 'subdistricts',  'uuid' => 'subdistrict_uuid'],
                'village_id'     => ['master' => 'villages',      'uuid' => 'village_uuid'],
                'sls_id'         => ['master' => 'sls',           'uuid' => 'sls_uuid'],
            ],

            // =========================
            // MARKET & STATUS
            // =========================
            'markets' => [
                'regency_id'     => ['master' => 'regencies',     'uuid' => 'regency_uuid'],
                'subdistrict_id' => ['master' => 'subdistricts',  'uuid' => 'subdistrict_uuid'],
                'village_id'     => ['master' => 'villages',      'uuid' => 'village_uuid'],
            ],

            'market_upload_status' => [
                'regency_id' => ['master' => 'regencies', 'uuid' => 'regency_uuid'],
            ],

            'supplement_upload_status' => [
                'regency_id'     => ['master' => 'regencies',    'uuid' => 'regency_uuid'],
                'subdistrict_id' => ['master' => 'subdistricts', 'uuid' => 'subdistrict_uuid'],
                'village_id'     => ['master' => 'villages',     'uuid' => 'village_uuid'],
                'sls_id'         => ['master' => 'sls',          'uuid' => 'sls_uuid'],
            ],

            // =========================
            // REPORTS
            // =========================
            'report_regency' => [
                'regency_id' => ['master' => 'regencies', 'uuid' => 'regency_uuid'],
            ],

            'report_subdistrict' => [
                'subdistrict_id' => ['master' => 'subdistricts', 'uuid' => 'subdistrict_uuid'],
            ],

            'report_village' => [
                'village_id' => ['master' => 'villages', 'uuid' => 'village_uuid'],
            ],

            'report_sls' => [
                'sls_id' => ['master' => 'sls', 'uuid' => 'sls_uuid'],
            ],

            // =========================
            // MASTER TABLES (hierarchical)
            // =========================
            'subdistricts' => [
                'regency_id' => ['master' => 'regencies', 'uuid' => 'regency_uuid'],
            ],

            'villages' => [
                'subdistrict_id' => ['master' => 'subdistricts', 'uuid' => 'subdistrict_uuid'],
            ],

            'sls' => [
                'village_id' => ['master' => 'villages', 'uuid' => 'village_uuid'],
            ],

            // =========================
            // SURVEY / USER
            // =========================
            'survey_business' => [
                'regency_id'     => ['master' => 'regencies',     'uuid' => 'regency_uuid'],
                'subdistrict_id' => ['master' => 'subdistricts',  'uuid' => 'subdistrict_uuid'],
                'village_id'     => ['master' => 'villages',      'uuid' => 'village_uuid'],
                'sls_id'         => ['master' => 'sls',           'uuid' => 'sls_uuid'],
            ],

            // 👇 SPECIAL CASE (FIXED)
            'user_acting_contexts' => [
                'acting_reg_id' => ['master' => 'regencies', 'uuid' => 'regency_uuid'],
            ],

            'users' => [
                'regency_id' => ['master' => 'regencies', 'uuid' => 'regency_uuid'],
            ],

            // =========================
            // SLS RELATED
            // =========================
            'sls_update_prelist' => [
                'sls_id' => ['master' => 'sls', 'uuid' => 'sls_uuid'],
            ],

            'sls_user_wilkerstat' => [
                'sls_id' => ['master' => 'sls', 'uuid' => 'sls_uuid'],
            ],
        ];

        /**
         * STEP 1: Ensure UUID indexes exist (safe re-run)
         */
        foreach ($map as $table => $relations) {
            foreach ($relations as $config) {

                $uuidColumn = $config['uuid'];

                if (!Schema::hasColumn($table, $uuidColumn)) {
                    continue;
                }

                $indexName = "{$table}_{$uuidColumn}_idx";

                if ($this->indexExists($table, $indexName)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $tableSchema) use ($uuidColumn, $indexName) {
                    $tableSchema->index($uuidColumn, $indexName);
                });
            }
        }

        /**
         * STEP 2: Batched UUID Backfill (MySQL-safe)
         */
        foreach ($map as $table => $relations) {
            foreach ($relations as $fkColumn => $config) {

                $masterTable = $config['master'];
                $uuidColumn  = $config['uuid'];

                do {
                    $affected = DB::affectingStatement("
                        UPDATE {$table} t
                        JOIN {$masterTable} m
                          ON m.id = t.{$fkColumn}
                        JOIN (
                            SELECT id
                            FROM {$table}
                            WHERE {$uuidColumn} IS NULL
                            LIMIT {$this->batchSize}
                        ) x ON x.id = t.id
                        SET t.{$uuidColumn} = m.uuid
                    ");
                } while ($affected > 0);
            }
        }
    }

    public function down(): void
    {
        // Intentionally empty (one-way data migration)
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
