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
        Schema::create('report_supplement', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('uploaded')->default(0);
            $table->enum('type', ['swmaps supplement', 'kendedes mobile', 'swmaps market', 'wilkerstat', 'jenggala', 'survey', 'other']);
            $table->string('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->index('organization_id');
            $table->timestamp('created_at')->default('2025-04-13 00:00:00');
            $table->timestamp('updated_at')->default('2025-04-13 00:00:00');
            $table->date('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_supplement');
    }
};
