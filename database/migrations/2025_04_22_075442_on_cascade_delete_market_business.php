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
        Schema::table('market_business', function (Blueprint $table) {
            // First, drop the existing foreign key
            $table->dropForeign(['market_id']);
        });

        Schema::table('market_business', function (Blueprint $table) {
            // Recreate the foreign key with ON DELETE CASCADE
            $table->foreign('market_id')
                ->references('id')
                ->on('markets')
                ->onDelete('cascade');
        });

        Schema::table('market_upload_status', function (Blueprint $table) {
            $table->dropForeign(['market_id']);
        });

        Schema::table('market_upload_status', function (Blueprint $table) {
            $table->foreign('market_id')
                ->references('id')
                ->on('markets')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('market_business', function (Blueprint $table) {
            // Drop the cascading foreign key
            $table->dropForeign(['market_id']);
        });

        Schema::table('market_business', function (Blueprint $table) {
            // Recreate original foreign key without cascading
            $table->foreign('market_id')
                ->references('id')
                ->on('markets');
        });

        Schema::table('market_upload_status', function (Blueprint $table) {
            $table->dropForeign(['market_id']);
        });

        Schema::table('market_upload_status', function (Blueprint $table) {
            $table->foreign('market_id')
                ->references('id')
                ->on('markets');
        });
    }
};
