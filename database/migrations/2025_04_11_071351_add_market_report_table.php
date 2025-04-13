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
        Schema::create('report_market_business_regency', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('uploaded')->default(0);
            $table->integer('total_market')->default(0);
            $table->string('regency_id');
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->index('regency_id');
            $table->date('date');
        });

        Schema::create('report_market_business_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('uploaded')->default(0);
            $table->string('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->index('user_id');
            $table->string('regency_id')->nullable();
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->index('regency_id');
            $table->date('date');
        });

        Schema::create('report_market_business_market', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('uploaded')->default(0);
            $table->string('market_id');
            $table->foreign('market_id')->references('id')->on('markets');
            $table->index('market_id');
            $table->string('regency_id')->nullable();
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->index('regency_id');
            $table->date('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_market_regency', function (Blueprint $table) {
            $table->dropForeign(['regency_id']);
            $table->dropIndex(['regency_id']);
        });
        Schema::dropIfExists('report_market_regency');

    }
};
