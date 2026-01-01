<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $masterTables = ['regencies', 'subdistricts', 'villages', 'sls'];

    /**
     * Switch PK from old ID to UUID, then rename UUID to ID
     */
    public function up(): void
    {
        foreach ($this->masterTables as $table) {
            // First, drop the unique index on uuid (since we'll make it primary)
            try {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->dropUnique(['uuid']);
                });
            } catch (\Throwable $e) {
                // ignore if doesn't exist
            }

            Schema::table($table, function (Blueprint $tableBlueprint) {
                // Drop old primary key
                $tableBlueprint->dropPrimary();
                
                // Set uuid as primary key
                $tableBlueprint->primary('uuid');
            });

            Schema::table($table, function (Blueprint $tableBlueprint) {
                // Drop old id column
                $tableBlueprint->dropColumn('id');
            });

            Schema::table($table, function (Blueprint $tableBlueprint) {
                // Rename uuid to id
                $tableBlueprint->renameColumn('uuid', 'id');
            });

            // Drop unique index on long_code if exists
            try {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->dropUnique(['long_code']);
                });
            } catch (\Throwable $e) {
                // ignore if doesn't exist
            }
        }
    }

    /**
     * Reverse: restore old ID as PK and rename back to UUID
     */
    public function down(): void
    {
        foreach ($this->masterTables as $table) {
            // Drop unique index on id (regencies_uuid_unique) if exists
            try {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->dropUnique(['id']);
                });
            } catch (\Throwable $e) {
                // ignore if doesn't exist
            }

            // Drop id (uuid) as primary key
            Schema::table($table, function (Blueprint $tableBlueprint) {
                $tableBlueprint->dropPrimary();
            });

            Schema::table($table, function (Blueprint $tableBlueprint) {
                // Rename id back to uuid
                $tableBlueprint->renameColumn('id', 'uuid');
            });

            Schema::table($table, function (Blueprint $tableBlueprint) {
                // Create new id column (varchar)
                $tableBlueprint->string('id', 255)->first();
            });

            // Update id column with long_code values
            DB::table($table)->update(['id' => DB::raw('long_code')]);

            Schema::table($table, function (Blueprint $tableBlueprint) {
                // Set id as primary key
                $tableBlueprint->primary('id');
            });
        }
    }
};
