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
        Schema::create('non_sls_business', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('owner')->nullable();
            $table->text('address')->nullable();
            $table->string('source');
            $table->text('initial_address')->nullable();
            $table->string('category')->nullable();
            $table->string('kbli')->nullable();
            $table->string('idsbr')->nullable();

            $table->string('regency_id');
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->string('subdistrict_id')->nullable();
            $table->foreign('subdistrict_id')->references('id')->on('subdistricts');
            $table->string('village_id')->nullable();
            $table->foreign('village_id')->references('id')->on('villages');
            $table->string('sls_id')->nullable();
            $table->foreign('sls_id')->references('id')->on('sls');

            $table->enum('level', ['regency', 'subdistrict', 'village']);

            $table->string('note')->nullable();
            $table->foreignId('status_id')->constrained('statuses');
            $table->foreignUuid('pml_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('pcl_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('last_modified_by')->nullable()->constrained('users')->onDelete('set null');

            $table->boolean('is_new')->default(false);
            $table->softDeletes();

            $table->index('regency_id');
            $table->index('subdistrict_id');
            $table->index('village_id');
            $table->index('sls_id');
            $table->fullText('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('non_sls_business');
    }
};
