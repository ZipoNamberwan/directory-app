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
        Schema::create('markets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('address');
            $table->string('regency_id');
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->string('subdistrict_id')->nullable();
            $table->foreign('subdistrict_id')->references('id')->on('subdistricts');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('market_business', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('owner')->nullable();
            $table->string('note')->nullable();
            $table->string('latitude');
            $table->string('longitude');
            $table->foreignUuid('market_id')->constrained('markets');
            $table->foreignUuid('user_id')->constrained('users');
            $table->string('regency_id');
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_business');
        Schema::dropIfExists('markets');
    }
};
