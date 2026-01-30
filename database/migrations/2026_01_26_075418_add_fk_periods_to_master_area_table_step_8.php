<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\DatabaseSelector;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sourceConnection = DatabaseSelector::getDefaultConnection();
        $targetConnection = DB::getDefaultConnection();

        // Get UUID from MAIN DB
        $period = DB::connection($sourceConnection)
            ->table('area_periods')
            ->orderBy('created_at')
            ->first();

        if (! $period) {
            throw new RuntimeException('area_periods is empty on source database');
        }

        $uuid = $period->id;

        foreach (['regencies', 'subdistricts', 'villages', 'sls'] as $tableName) {

            // 1️⃣ Add column
            Schema::connection($targetConnection)
                ->table($tableName, function (Blueprint $table) {
                    $table->uuid('area_period_id')->nullable();
                });

            // 2️⃣ Fill data
            DB::connection($targetConnection)
                ->table($tableName)
                ->update(['area_period_id' => $uuid]);

            // 3️⃣ Add FK + index
            Schema::connection($targetConnection)
                ->table($tableName, function (Blueprint $table) {
                    $table->index('area_period_id');

                    $table->foreign('area_period_id')
                          ->references('id')
                          ->on('area_periods')
                          ->nullOnDelete();
                });
        }
    }

    public function down(): void
    {
        $targetConnection = DB::getDefaultConnection();

        foreach (['regencies', 'subdistricts', 'villages', 'sls'] as $tableName) {
            Schema::connection($targetConnection)
                ->table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['area_period_id']);
                    $table->dropIndex(['area_period_id']);
                    $table->dropColumn('area_period_id');
                });
        }
    }
};
