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
        Schema::create('agriculture_business', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('name');
            $table->string('status')->nullable();
            $table->string('address')->nullable();
            $table->string('description')->nullable();
            $table->string('sector')->nullable();
            $table->string('note')->nullable();
            $table->decimal('latitude', 12, 10);
            $table->decimal('longitude', 13, 10);
            $table->char('regency_id', 36)->nullable()->index();
            $table->char('subdistrict_id', 36)->nullable()->index();
            $table->char('village_id', 36)->nullable()->index();
            $table->char('sls_id', 36)->nullable()->index();
            $table->timestamps(0);
            $table->softDeletes('deleted_at', 0);
            $table->string('owner')->nullable();
            $table->string('id_agriculture')->nullable()->index();
            $table->index(['latitude', 'longitude'], 'idx_lat_long');
            $table->index('latitude', 'idx_latitude');
            $table->index('longitude', 'idx_longitude');

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
        });

        DB::statement('ALTER TABLE agriculture_business ADD COLUMN coordinate POINT NOT NULL SRID 4326');
        DB::statement('ALTER TABLE agriculture_business ADD SPATIAL INDEX idx_agriculture_business_coordinate (coordinate)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agriculture_business');
    }
};
