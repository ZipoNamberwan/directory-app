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
        Schema::create('regencies', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('short_code');
            $table->string('long_code')->unique();
            $table->string('name');
        });
        Schema::create('subdistricts', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('short_code');
            $table->string('long_code')->unique();
            $table->string('name');
            // $table->foreignId('regency_id')->constrained('regencies');
            $table->string('regency_id');
            $table->foreign('regency_id')->references('id')->on('regencies')->onDelete('cascade');
        });
        Schema::create('villages', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('short_code');
            $table->string('long_code')->unique();
            $table->string('name');
            // $table->foreignId('subdistrict_id')->constrained('subdistricts');
            $table->string('subdistrict_id');
            $table->foreign('subdistrict_id')->references('id')->on('subdistricts')->onDelete('cascade');
        });
        Schema::create('sls', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('short_code');
            $table->string('long_code')->unique();
            // $table->enum('type', ['SLS', 'Non SLS']);
            $table->string('name');
            // $table->foreignId('village_id')->constrained('villages');
            $table->string('village_id');
            $table->foreign('village_id')->references('id')->on('villages')->onDelete('cascade');
        });

        Schema::create('statuses', function (Blueprint $table) {
            $table->id()->autoincrement();
            $table->string('name');
        });

        Schema::create('categorized_business', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('owner')->nullable();
            // $table->foreignId('regency_id')->nullable()->constrained('regencies');
            // $table->foreignId('subdistrict_id')->nullable()->constrained('subdistricts');
            // $table->foreignId('village_id')->nullable()->constrained('villages');
            // $table->foreignId('sls_id')->nullable()->constrained('sls');
            
            $table->string('regency_id')->nullable();
            $table->foreign('regency_id')->references('id')->on('regencies')->onDelete('cascade');
            $table->string('subdistrict_id')->nullable();
            $table->foreign('subdistrict_id')->references('id')->on('subdistricts')->onDelete('cascade');
            $table->string('village_id')->nullable();
            $table->foreign('village_id')->references('id')->on('villages')->onDelete('cascade');
            $table->string('sls_id')->nullable();
            $table->foreign('sls_id')->references('id')->on('sls')->onDelete('cascade');

            $table->string('note')->nullable();
            $table->foreignId('status_id')->nullable()->constrained('statuses');
            $table->foreignId('pml_id')->nullable()->constrained('users');
            $table->foreignId('ppl_id')->nullable()->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regencies');
        Schema::dropIfExists('subdistricts');
        Schema::dropIfExists('villages');
        Schema::dropIfExists('sls');
    }
};
