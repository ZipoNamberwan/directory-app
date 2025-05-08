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
        Schema::create('supplement_upload_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('filename');
            $table->text('message')->nullable();
            $table->enum('status', ['start', 'loading', 'processing', 'success', 'failed', 'success with error']);
            $table->foreignUuid('user_id')->constrained('users');

            $table->string('regency_id')->nullable();
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->string('subdistrict_id')->nullable();
            $table->foreign('subdistrict_id')->references('id')->on('subdistricts');
            $table->string('village_id')->nullable();
            $table->foreign('village_id')->references('id')->on('villages');
            $table->string('sls_id')->nullable();
            $table->foreign('sls_id')->references('id')->on('sls');

            $table->string('organization_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations');

            $table->string('user_firstname');
            $table->string('area')->nullable();

            $table->timestamps();
        });

        Schema::create('supplement_business', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('status')->nullable();
            $table->string('address')->nullable();
            $table->string('description')->nullable();
            $table->string('sector')->nullable();
            $table->string('note')->nullable();

            $table->string('latitude');
            $table->string('longitude');

            $table->string('regency_id')->nullable();
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->string('subdistrict_id')->nullable();
            $table->foreign('subdistrict_id')->references('id')->on('subdistricts');
            $table->string('village_id')->nullable();
            $table->foreign('village_id')->references('id')->on('villages');
            $table->string('sls_id')->nullable();
            $table->foreign('sls_id')->references('id')->on('sls');

            $table->string('organization_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('upload_id')->constrained('supplement_upload_status');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplement_business');
        Schema::dropIfExists('supplement_upload_status');
    }
};
