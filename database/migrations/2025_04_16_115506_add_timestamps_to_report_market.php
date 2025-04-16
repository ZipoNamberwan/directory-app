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
            $table->timestamp('created_at')->default('2025-04-13 00:00:00');
            $table->timestamp('updated_at')->default('2025-04-13 00:00:00');
        });
        Schema::table('report_market_business_user', function (Blueprint $table) {
            $table->timestamp('created_at')->default('2025-04-13 00:00:00');
            $table->timestamp('updated_at')->default('2025-04-13 00:00:00');
        });
        Schema::table('report_market_business_market', function (Blueprint $table) {
            $table->timestamp('created_at')->default('2025-04-13 00:00:00');
            $table->timestamp('updated_at')->default('2025-04-13 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_market_business_regency', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
        Schema::table('report_market_business_user', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
        Schema::table('report_market_business_market', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }
};
