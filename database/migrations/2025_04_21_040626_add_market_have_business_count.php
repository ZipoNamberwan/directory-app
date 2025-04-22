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
        Schema::table('report_market_business_regency', function (Blueprint $table) {
            $table->integer('market_have_business')->default(0);
        });

        Schema::table('markets', function (Blueprint $table) {
            $table->boolean('done_by_prov')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_market_business_regency', function (Blueprint $table) {
            $table->dropColumn('market_have_business');
        });
        
        Schema::table('markets', function (Blueprint $table) {
            $table->dropColumn('done_by_prov');
        });
    }
};
