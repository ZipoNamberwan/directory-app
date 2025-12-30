<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * MATRIX MAPPING
         * table_name => [ fk_column => master_table ]
         */
        $map = [

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
            // SURVEY / USER
            // =========================
            'survey_business' => [
                'regency_id'     => 'regencies',
                'subdistrict_id' => 'subdistricts',
                'village_id'     => 'villages',
                'sls_id'         => 'sls',
            ],

            'user_acting_context' => [
                'regency_id' => 'regencies',
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

        DB::beginTransaction();

        try {
            foreach ($map as $table => $relations) {
                foreach ($relations as $fkColumn => $masterTable) {

                    $uuidColumn = str_replace('_id', '_uuid', $fkColumn);

                    $sql = "
                        UPDATE {$table} t
                        JOIN {$masterTable} m
                          ON m.id = t.{$fkColumn}
                        SET t.{$uuidColumn} = m.uuid
                        WHERE t.{$uuidColumn} IS NULL
                    ";

                    DB::statement($sql);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function down(): void
    {
        // Intentionally left empty.
        // This is a one-way data migration.
    }
};
