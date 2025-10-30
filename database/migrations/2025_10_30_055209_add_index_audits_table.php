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
        Schema::table('audits', function (Blueprint $table) {
            // Add individual indexes for common query patterns
            $table->index('model_type');
            $table->index('model_id');
            $table->index('edited_by');
            
            // Add composite index for the most common query pattern (model_type + model_id)
            $table->index(['model_type', 'model_id'], 'audits_model_type_model_id_index');
            
            // Add composite index for queries filtering by model and editor
            $table->index(['model_type', 'model_id', 'edited_by'], 'audits_model_editor_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            // Drop composite indexes first
            $table->dropIndex('audits_model_editor_index');
            $table->dropIndex('audits_model_type_model_id_index');
            
            // Drop individual indexes
            $table->dropIndex(['edited_by']);
            $table->dropIndex(['model_id']);
            $table->dropIndex(['model_type']);
        });
    }
};
