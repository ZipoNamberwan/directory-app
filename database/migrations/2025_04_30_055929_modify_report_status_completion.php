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
            $table->integer('target')->default(0);
            $table->integer('non_target')->default(0);
            $table->integer('not_start')->default(0);
            $table->integer('on_going')->default(0);
            $table->integer('done')->default(0);
        });

        Schema::table('report_market_business_market', function (Blueprint $table) {
            $table->enum('completion_status', ['not start', 'on going', 'done'])->default('not start');;
            $table->enum('target_category', ['target', 'non target'])->default('target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
