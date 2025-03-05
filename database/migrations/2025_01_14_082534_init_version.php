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
            $table->string('regency_id');
            $table->foreign('regency_id')->references('id')->on('regencies');
        });
        Schema::create('villages', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('short_code');
            $table->string('long_code')->unique();
            $table->string('name');
            $table->string('subdistrict_id');
            $table->foreign('subdistrict_id')->references('id')->on('subdistricts');
        });
        Schema::create('sls', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('short_code');
            $table->string('long_code')->unique();
            $table->string('name');
            $table->string('village_id');
            $table->foreign('village_id')->references('id')->on('villages');
        });

        Schema::create('statuses', function (Blueprint $table) {
            $table->id()->autoincrement();
            $table->string('name');
            $table->string('color')->nullable();
            $table->string('code')->nullable();
            $table->integer('order');
        });

        Schema::create('sls_business', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('owner')->nullable();
            $table->string('source');
            $table->text('initial_address')->nullable();
            $table->string('category')->nullable();
            $table->string('kbli')->nullable();
            $table->string('lat')->nullable();
            $table->string('long')->nullable();

            $table->string('regency_id');
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->string('subdistrict_id');
            $table->foreign('subdistrict_id')->references('id')->on('subdistricts');
            $table->string('village_id');
            $table->foreign('village_id')->references('id')->on('villages');
            $table->string('sls_id');
            $table->foreign('sls_id')->references('id')->on('sls');

            $table->string('note')->nullable();
            $table->foreignId('status_id')->constrained('statuses');
            $table->foreignUuid('pml_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('pcl_id')->nullable()->constrained('users')->onDelete('set null');

            $table->boolean('is_new')->default(false);
            $table->softDeletes();

            $table->index('regency_id');
            $table->index('subdistrict_id');
            $table->index('village_id');
            $table->index('sls_id');
            $table->index('status_id');
            $table->fullText('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('regency_id')->nullable();
            $table->foreign('regency_id')->references('id')->on('regencies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['regency_id']);
            $table->dropColumn('regency_id');
        });

        Schema::table('sls_business', function (Blueprint $table) {
            $table->dropForeign(['regency_id']);
            $table->dropForeign(['subdistrict_id']);
            $table->dropForeign(['village_id']);
            $table->dropForeign(['sls_id']);
            $table->dropForeign(['status_id']);
            $table->dropForeign(['pml_id']);
            $table->dropForeign(['pcl_id']);
        });

        Schema::dropIfExists('sls_business');
        Schema::dropIfExists('statuses');

        Schema::table('sls', function (Blueprint $table) {
            $table->dropForeign(['village_id']);
        });

        Schema::dropIfExists('sls');

        Schema::table('villages', function (Blueprint $table) {
            $table->dropForeign(['subdistrict_id']);
        });

        Schema::dropIfExists('villages');

        Schema::table('subdistricts', function (Blueprint $table) {
            $table->dropForeign(['regency_id']);
        });

        Schema::dropIfExists('subdistricts');
        Schema::dropIfExists('regencies');
    }
};
