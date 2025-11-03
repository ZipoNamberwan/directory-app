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
        Schema::table('duplicate_candidates', function (Blueprint $table) {
            $table->unsignedTinyInteger('ai_recommendation')->nullable()->checkBetween(1, 10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('duplicate_candidates', function (Blueprint $table) {
            $table->dropColumn('ai_recommendation');
        });
    }
};
