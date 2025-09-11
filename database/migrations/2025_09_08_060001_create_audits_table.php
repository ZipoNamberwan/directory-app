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
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->string('table_name');
            $table->uuid('model_id');
            $table->string('column_name');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->uuid('edited_by')->nullable();
            $table->string('medium')->nullable();
            $table->timestamp('edited_at');
        });

        // Indexes for faster lookup
        Schema::table('audits', function (Blueprint $table) {
            $table->index(['table_name', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
