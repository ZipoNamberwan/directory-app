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
        // report_market_business_regency
        Schema::table('report_market_business_regency', function (Blueprint $table) {
            $table->dropForeign(['regency_id']);
            $table->dropIndex(['regency_id']);
            $table->renameColumn('regency_id', 'organization_id');
        });

        Schema::table('report_market_business_regency', function (Blueprint $table) {
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->index('organization_id');
        });

        // report_market_business_user
        Schema::table('report_market_business_user', function (Blueprint $table) {
            $table->dropForeign(['regency_id']);
            $table->dropIndex(['regency_id']);
            $table->renameColumn('regency_id', 'organization_id');
        });

        Schema::table('report_market_business_user', function (Blueprint $table) {
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->index('organization_id');
        });

        // report_market_business_market
        Schema::table('report_market_business_market', function (Blueprint $table) {
            $table->dropForeign(['regency_id']);
            $table->dropIndex(['regency_id']);
            $table->renameColumn('regency_id', 'organization_id');
        });

        Schema::table('report_market_business_market', function (Blueprint $table) {
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->index('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // report_market_business_regency
        Schema::table('report_market_business_regency', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id']);
            $table->renameColumn('organization_id', 'regency_id');
        });

        Schema::table('report_market_business_regency', function (Blueprint $table) {
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->index('regency_id');
        });

        // report_market_business_user
        Schema::table('report_market_business_user', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id']);
            $table->renameColumn('organization_id', 'regency_id');
        });

        Schema::table('report_market_business_user', function (Blueprint $table) {
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->index('regency_id');
        });

        // report_market_business_market
        Schema::table('report_market_business_market', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id']);
            $table->renameColumn('organization_id', 'regency_id');
        });

        Schema::table('report_market_business_market', function (Blueprint $table) {
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->index('regency_id');
        });
    }
};
