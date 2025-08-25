<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('supplement_business', function (Blueprint $table) {
            $table->dateTime('matched_at')->nullable()->after('deleted_at');
            $table->enum('match_level', ['failed', 'noarea', 'sls', 'village', 'subdistrict', 'regency'])
              ->nullable()
              ->after('matched_at');
        });

        Schema::table('market_business', function (Blueprint $table) {
            $table->string('subdistrict_id')->nullable();
            $table->foreign('subdistrict_id')->references('id')->on('subdistricts');
            $table->string('village_id')->nullable();
            $table->foreign('village_id')->references('id')->on('villages');
            $table->string('sls_id')->nullable();
            $table->foreign('sls_id')->references('id')->on('sls');

            $table->dateTime('matched_at')->nullable()->after('deleted_at');
            $table->enum('match_level', ['failed', 'noarea', 'sls', 'village', 'subdistrict', 'regency'])
              ->nullable()
              ->after('matched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplement_business', function (Blueprint $table) {
            $table->dropColumn(['matched_at', 'match_level']);
        });
        Schema::table('market_business', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['subdistrict_id']);
            $table->dropForeign(['village_id']);
            $table->dropForeign(['sls_id']);

            // Then drop the columns
            $table->dropColumn([
                'subdistrict_id',
                'village_id',
                'sls_id',
                'matched_at',
                'match_level'
            ]);
        });
    }
};
