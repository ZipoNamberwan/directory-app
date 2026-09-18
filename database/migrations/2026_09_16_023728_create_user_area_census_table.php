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
        Schema::create('user_sls_census', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('sls_id');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sls_id')->references('id')->on('sls')->onDelete('cascade');
        });

        Schema::table('enumeration_business', function (Blueprint $table) {
            $table->string('building_number')->nullable();
            $table->decimal('original_latitude', 12, 10);
            $table->decimal('original_longitude', 13, 10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sls_census');

        Schema::table('enumeration_business', function (Blueprint $table) {
            $table->dropColumn('building_number');
        });
    }
};
