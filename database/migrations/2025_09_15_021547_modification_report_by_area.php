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
        Schema::table('report_province', function (Blueprint $table) {
            // Remove columns
            $table->dropColumn('not_update');
            $table->dropColumn('exist');
            $table->dropColumn('not_exist');
            $table->dropColumn('not_scope');
            $table->dropColumn('new');
            $table->dropColumn('date');
            $table->dropColumn('type');

            $table->enum('business_type', ['market', 'supplement', 'kenarok']);
            $table->integer('total')->default(0);
            $table->timestamps();
        });

        Schema::table('report_regency', function (Blueprint $table) {
            // Remove columns
            $table->dropColumn('not_update');
            $table->dropColumn('exist');
            $table->dropColumn('not_exist');
            $table->dropColumn('not_scope');
            $table->dropColumn('new');
            $table->dropColumn('date');
            $table->dropColumn('type');

            $table->enum('business_type', ['market', 'supplement', 'kenarok']);
            $table->integer('total')->default(0);
            $table->timestamps();
        });

        Schema::table('report_subdistrict', function (Blueprint $table) {
            // Remove columns
            $table->dropColumn('not_update');
            $table->dropColumn('exist');
            $table->dropColumn('not_exist');
            $table->dropColumn('not_scope');
            $table->dropColumn('new');
            $table->dropColumn('date');
            $table->dropColumn('type');

            $table->enum('business_type', ['market', 'supplement', 'kenarok']);
            $table->integer('total')->default(0);
            $table->timestamps();
        });

        Schema::table('report_village', function (Blueprint $table) {
            // Remove columns
            $table->dropColumn('not_update');
            $table->dropColumn('exist');
            $table->dropColumn('not_exist');
            $table->dropColumn('not_scope');
            $table->dropColumn('new');
            $table->dropColumn('date');
            $table->dropColumn('type');

            $table->enum('business_type', ['market', 'supplement', 'kenarok']);
            $table->integer('total')->default(0);
            $table->timestamps();
        });

        Schema::create('report_sls', function (Blueprint $table) {
            // Remove columns
            $table->uuid('id')->primary();

            $table->enum('business_type', ['market', 'supplement', 'kenarok']);
            $table->integer('total')->default(0);
            $table->string('sls_id')->nullable();
            $table->foreign('sls_id')->references('id')->on('sls');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ===== report_province =====
        Schema::table('report_province', function (Blueprint $table) {
            // restore old columns
            $table->integer('not_update')->default(0);
            $table->integer('exist')->default(0);
            $table->integer('not_exist')->default(0);
            $table->integer('not_scope')->default(0);
            $table->integer('new')->default(0);
            $table->date('date')->nullable();
            $table->enum('type', ['sls', 'non_sls']);

            // remove the new columns
            $table->dropColumn(['total', 'created_at', 'updated_at', 'business_type']);
        });

        // ===== report_regency =====
        Schema::table('report_regency', function (Blueprint $table) {
            $table->integer('not_update')->default(0);
            $table->integer('exist')->default(0);
            $table->integer('not_exist')->default(0);
            $table->integer('not_scope')->default(0);
            $table->integer('new')->default(0);
            $table->date('date')->nullable();
            $table->enum('type', ['sls', 'non_sls']);

            $table->dropColumn(['total', 'created_at', 'updated_at', 'business_type']);
        });

        // ===== report_subdistrict =====
        Schema::table('report_subdistrict', function (Blueprint $table) {
            $table->integer('not_update')->default(0);
            $table->integer('exist')->default(0);
            $table->integer('not_exist')->default(0);
            $table->integer('not_scope')->default(0);
            $table->integer('new')->default(0);
            $table->date('date')->nullable();
            $table->enum('type', ['sls', 'non_sls']);

            $table->dropColumn(['total', 'created_at', 'updated_at', 'business_type']);
        });

        // ===== report_village =====
        Schema::table('report_village', function (Blueprint $table) {
            $table->integer('not_update')->default(0);
            $table->integer('exist')->default(0);
            $table->integer('not_exist')->default(0);
            $table->integer('not_scope')->default(0);
            $table->integer('new')->default(0);
            $table->date('date')->nullable();
            $table->enum('type', ['sls', 'non_sls']);

            $table->dropColumn(['total', 'created_at', 'updated_at', 'business_type']);
        });

        // ===== report_sls =====
        Schema::dropIfExists('report_sls');
    }
};
