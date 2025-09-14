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
        Schema::table('supplement_business', function (Blueprint $table) {
            $table->dateTime('checked_at')->nullable()->after('matched_at');
        });
        Schema::table('market_business', function (Blueprint $table) {
            $table->dateTime('checked_at')->nullable()->after('matched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplement_business', function (Blueprint $table) {
            $table->dropColumn(['checked_at']);
        });
        Schema::table('market_business', function (Blueprint $table) {
            $table->dropColumn(['checked_at']);
        });
    }
};
