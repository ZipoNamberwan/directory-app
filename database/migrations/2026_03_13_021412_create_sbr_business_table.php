<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sbr_business', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('name');
            $table->string('status')->nullable();
            $table->string('address')->nullable();
            $table->string('description')->nullable();
            $table->string('sector')->nullable();
            $table->string('note')->nullable();
            $table->decimal('latitude', 12, 10);
            $table->decimal('longitude', 13, 10);

            // Remove the point() column from Blueprint
            // We'll add it using raw SQL below

            $table->char('regency_id', 36)->nullable()->index();
            $table->char('subdistrict_id', 36)->nullable()->index();
            $table->char('village_id', 36)->nullable()->index();
            $table->char('sls_id', 36)->nullable()->index();
            $table->timestamps(0);
            $table->softDeletes('deleted_at', 0);
            $table->string('owner')->nullable();

            // Foreign key constraints
            $table->foreign('regency_id')
                ->references('id')->on('regencies')
                ->onDelete('restrict')->onUpdate('cascade');

            $table->foreign('subdistrict_id')
                ->references('id')->on('subdistricts')
                ->onDelete('restrict')->onUpdate('cascade');

            $table->foreign('village_id')
                ->references('id')->on('villages')
                ->onDelete('restrict')->onUpdate('cascade');

            $table->foreign('sls_id')
                ->references('id')->on('sls')
                ->onDelete('restrict')->onUpdate('cascade');

            // Regular column with index
            $table->string('idsbr')->nullable()->index();
            $table->integer('status_sbr')->nullable()->index();
        });

        // Add the coordinate column and spatial index using raw SQL
        DB::statement('ALTER TABLE sbr_business ADD COLUMN coordinate POINT NOT NULL SRID 4326');
        DB::statement('ALTER TABLE sbr_business ADD SPATIAL INDEX idx_sbr_business_coordinate (coordinate)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sbr_business');
    }
};
