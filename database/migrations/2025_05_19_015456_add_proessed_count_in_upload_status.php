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
        Schema::table('market_upload_status', function (Blueprint $table) {
            $table->integer('processed_count')->default(0)->after('status');
        });

        Schema::table('supplement_upload_status', function (Blueprint $table) {
            $table->integer('processed_count')->default(0)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('market_upload_status', function (Blueprint $table) {
            $table->dropColumn('processed_count');
        });

        Schema::table('supplement_upload_status', function (Blueprint $table) {
            $table->dropColumn('processed_count');
        });
    }
};
