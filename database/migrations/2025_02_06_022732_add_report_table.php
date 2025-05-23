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
        Schema::create('report_village', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('not_update')->default(0);
            $table->integer('exist')->default(0);
            $table->integer('not_exist')->default(0);
            $table->integer('not_scope')->default(0);
            $table->integer('new')->default(0);
            // $table->integer('total')->default(0);
            $table->string('village_id')->nullable();
            $table->foreign('village_id')->references('id')->on('villages');
            $table->index('village_id');
            $table->date('date');
            $table->enum('type', ['sls', 'non_sls']);
        });

        Schema::create('report_subdistrict', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('not_update')->default(0);
            $table->integer('exist')->default(0);
            $table->integer('not_exist')->default(0);
            $table->integer('not_scope')->default(0);
            $table->integer('new')->default(0);
            // $table->integer('total')->default(0);
            $table->string('subdistrict_id')->nullable();
            $table->foreign('subdistrict_id')->references('id')->on('subdistricts');
            $table->index('subdistrict_id');
            $table->date('date');
            $table->enum('type', ['sls', 'non_sls']);
        });

        Schema::create('report_regency', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('not_update')->default(0);
            $table->integer('exist')->default(0);
            $table->integer('not_exist')->default(0);
            $table->integer('not_scope')->default(0);
            $table->integer('new')->default(0);
            // $table->integer('total')->default(0);
            $table->string('regency_id')->nullable();
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->index('regency_id');
            $table->date('date');
            $table->enum('type', ['sls', 'non_sls']);
        });

        Schema::create('report_province', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('not_update')->default(0);
            $table->integer('exist')->default(0);
            $table->integer('not_exist')->default(0);
            $table->integer('not_scope')->default(0);
            $table->integer('new')->default(0);
            // $table->integer('total')->default(0);
            $table->date('date');
            $table->enum('type', ['sls', 'non_sls']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_village', function (Blueprint $table) {
            $table->dropForeign(['village_id']);
            $table->dropIndex(['village_id']);
        });
    
        Schema::table('report_subdistrict', function (Blueprint $table) {
            $table->dropForeign(['subdistrict_id']);
            $table->dropIndex(['subdistrict_id']);
        });
    
        Schema::table('report_regency', function (Blueprint $table) {
            $table->dropForeign(['regency_id']);
            $table->dropIndex(['regency_id']);
        });
    
        Schema::dropIfExists('report_province');
        Schema::dropIfExists('report_regency');
        Schema::dropIfExists('report_subdistrict');
        Schema::dropIfExists('report_village');
    }
};
