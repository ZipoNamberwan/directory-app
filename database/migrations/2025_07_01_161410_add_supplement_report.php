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
        Schema::rename('report_supplement', 'report_supplement_business_regency');

        Schema::create('report_supplement_business_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('uploaded')->default(0);

            // Then add the new one with ON DELETE CASCADE
            $table->uuid('user_id');
            $table->uuid('organization_id');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
            $table->index('user_id');

            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->index('organization_id');

            $table->date('date');
            $table->timestamps();
        });

        Schema::create('report_total_business_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('market')->default(0);
            $table->integer('supplement')->default(0);
            $table->integer('total')->default(0);

            // Then add the new one with ON DELETE CASCADE
            $table->uuid('user_id');
            $table->uuid('organization_id');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
            $table->index('user_id');

            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->index('organization_id');

            $table->date('date');
            $table->timestamps();
        });

        Schema::create('report_total_business_regency', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('market')->default(0);
            $table->integer('supplement')->default(0);
            $table->integer('total')->default(0);

            $table->uuid('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->index('organization_id');

            $table->date('date');
            $table->timestamps();
        });

         DB::statement("ALTER TABLE assignment_status MODIFY COLUMN `type` ENUM(
            'export',
            'import',
            'download-sls-business',
            'download-non-sls-business',
            'import-business',
            'upload-market-assignment',
            'download-market-raw',
            'download-market-master',
            'download-supplement-business',
            'dashboard-regency',
            'dashboard-user',
            'dashboard-market',
            'dashboard-supplement'
        )");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('report_supplement_business_regency', 'report_supplement');

        Schema::dropIfExists('report_supplement_business_user');

        Schema::dropIfExists('report_total_business_user');

        Schema::dropIfExists('report_total_business_regency');

        DB::statement("ALTER TABLE assignment_status MODIFY COLUMN `type` ENUM(
            'export',
            'import',
            'download-sls-business',
            'download-non-sls-business',
            'import-business',
            'upload-market-assignment',
            'download-market-raw',
            'download-market-master',
            'download-supplement-business',
            'dashboard-regency',
            'dashboard-user',
            'dashboard-market'
        )");
    }
};
