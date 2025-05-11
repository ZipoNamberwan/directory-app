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
        Schema::table('report_market_business_market', function (Blueprint $table) {
            $table->foreignId('market_type_id')->nullable()->constrained('market_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_market_business_market', function (Blueprint $table) {
            $table->dropForeign(['market_type_id']);
            $table->dropColumn('market_type_id');
        });
    }
};
