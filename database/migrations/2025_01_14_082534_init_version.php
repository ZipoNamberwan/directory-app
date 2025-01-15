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
            $table->id()->autoincrement();
            $table->string('short_code');
            $table->string('long_code')->unique();
            $table->string('name');
        });
        Schema::create('subdistricts', function (Blueprint $table) {
            $table->id()->autoincrement();
            $table->string('short_code');
            $table->string('long_code')->unique();
            $table->string('name');
            $table->foreignId('regency_id')->constrained('regencies');
        });
        Schema::create('villages', function (Blueprint $table) {
            $table->id()->autoincrement();
            $table->string('short_code');
            $table->string('long_code')->unique();
            $table->string('name');
            $table->foreignId('subdistrict_id')->constrained('subdistricts');
        });
        Schema::create('sls', function (Blueprint $table) {
            $table->id()->autoincrement();
            $table->string('short_code');
            $table->string('long_code')->unique();
            // $table->enum('type', ['SLS', 'Non SLS']);
            $table->string('name');
            $table->foreignId('village_id')->constrained('villages');
        });

        Schema::create('statuses', function (Blueprint $table) {
            $table->id()->autoincrement();
            $table->string('name');
        });

        Schema::create('categorized_business', function (Blueprint $table) {
            $table->id()->autoincrement();
            $table->string('name');
            $table->string('owner');
            $table->foreignId('regency_id')->constrained('regencies');
            $table->foreignId('subdistrict_id')->nullable()->constrained('subdistricts');
            $table->foreignId('village_id')->nullable()->constrained('villages');
            $table->foreignId('sls_id')->nullable()->constrained('sls');
            $table->string('note')->nullable();
            $table->foreignId('status_id')->nullable()->constrained('statuses');

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
