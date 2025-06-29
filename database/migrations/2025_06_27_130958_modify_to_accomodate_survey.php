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
        DB::statement("ALTER TABLE projects MODIFY COLUMN `type` ENUM(
            'swmaps supplement',
            'kendedes mobile',
            'swmaps market',
            'wilkerstat',
            'jenggala',
            'survey',
            'other'
        )");

        Schema::create('surveys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->nullable();
        });

        Schema::create('survey_business', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('status')->nullable();
            $table->string('address')->nullable();
            $table->string('description')->nullable();
            $table->string('sector')->nullable();
            $table->string('note')->nullable();

            $table->decimal('latitude', 12, 10);
            $table->decimal('longitude', 13, 10);

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

            $table->foreignUuid('survey_id')->nullable()->constrained('surveys');

            $table->timestamps();
            $table->softDeletes();

            $table->index('latitude');
            $table->index('longitude');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_allowed_ios')->default(false);
            $table->integer('mobile_version_code')->nullable();
        });

        Schema::table('versions', function (Blueprint $table) {
            $table->string('version_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE projects MODIFY COLUMN `type` ENUM(
            'swmaps supplement',
            'kendedes mobile',
            'swmaps market',
            'wilkerstat',
            'jenggala'
        )");

        // Drop added columns from existing tables
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_allowed_ios');
            $table->dropColumn('mobile_version_code');
        });

        // Drop survey_business table foreign keys before dropping the table
        Schema::table('survey_business', function (Blueprint $table) {
            $table->dropForeign(['regency_id']);
            $table->dropForeign(['subdistrict_id']);
            $table->dropForeign(['village_id']);
            $table->dropForeign(['sls_id']);
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['survey_id']);
        });

        Schema::dropIfExists('survey_business');
        Schema::dropIfExists('surveys');

        Schema::table('versions', function (Blueprint $table) {
            $table->dropColumn('version_name');
        });
    }
};
