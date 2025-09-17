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
        Schema::table('market_business', function (Blueprint $table) {
            $table->boolean('is_etl')->default(false)->after('is_locked');
        });
        Schema::table('supplement_business', function (Blueprint $table) {
            $table->boolean('is_etl')->default(false)->after('is_locked');
        });
        Schema::table('survey_business', function (Blueprint $table) {
            $table->boolean('is_etl')->default(false)->after('owner');
        });
        Schema::table('sls_business', function (Blueprint $table) {
            $table->boolean('is_etl')->default(false)->after('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('market_business', function (Blueprint $table) {
            $table->dropColumn('is_etl');
        });

        Schema::table('supplement_business', function (Blueprint $table) {
            $table->dropColumn('is_etl');
        });

        Schema::table('survey_business', function (Blueprint $table) {
            $table->dropColumn('is_etl');
        });

        Schema::table('sls_business', function (Blueprint $table) {
            $table->dropColumn('is_etl');
        });
    }
};
