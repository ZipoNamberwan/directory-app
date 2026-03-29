<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sbr_business', function (Blueprint $table) {
            // Composite index
            $table->index(['latitude', 'longitude'], 'idx_lat_long');

            // Single index (choose one or both)
            $table->index('latitude', 'idx_latitude');
            $table->index('longitude', 'idx_longitude');
        });

        foreach (['supplement_business', 'market_business', 'survey_business'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->index(['latitude', 'longitude'], 'idx_lat_long');
            });
        }
    }

    public function down(): void
    {
        Schema::table('sbr_business', function (Blueprint $table) {
            // Drop indexes by name
            $table->dropIndex('idx_lat_long');
            $table->dropIndex('idx_latitude');
            $table->dropIndex('idx_longitude');
        });

        foreach (['supplement_business', 'market_business', 'survey_business'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->dropIndex('idx_lat_long');
            });
        }
    }
};
