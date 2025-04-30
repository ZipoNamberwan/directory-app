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
        Schema::table('markets', function (Blueprint $table) {
            $table->enum('completion_status', ['not start', 'on going', 'done'])->default('not start');;
            $table->enum('target_category', ['target', 'non target'])->default('target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->dropColumn(['completion_status', 'target_category']);
        });
    }
};
