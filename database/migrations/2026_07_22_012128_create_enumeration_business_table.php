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
        Schema::create('enumeration_business', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('name');
            $table->string('assignment_id')->index();
            $table->decimal('latitude', 12, 10);
            $table->decimal('longitude', 13, 10);
            $table->string('original_area')->nullable();

            $table->char('regency_id', 36)->nullable()->index();
            $table->char('subdistrict_id', 36)->nullable()->index();
            $table->char('village_id', 36)->nullable()->index();
            $table->char('sls_id', 36)->nullable()->index();
            $table->timestamps(0);
            $table->softDeletes('deleted_at', 0);

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
        });

        // Add the coordinate column and spatial index using raw SQL
        DB::statement('ALTER TABLE enumeration_business ADD COLUMN coordinate POINT NOT NULL SRID 4326');
        DB::statement('ALTER TABLE enumeration_business ADD SPATIAL INDEX idx_enumeration_business_coordinate (coordinate)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enumeration_business');
    }
};
