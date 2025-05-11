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
            $table->unsignedBigInteger('market_type_id')->nullable()->default(1);
            $table->foreign('market_type_id')->references('id')->on('market_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_market_business_regency', function (Blueprint $table) {
            // First drop the foreign key constraint
            $table->dropForeign(['market_type_id']);

            // Then drop the column itself
            $table->dropColumn('market_type_id');
        });
    }
};
