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
        Schema::table('market_business', function (Blueprint $table) {
            // Use raw statements to change column types
            DB::statement('ALTER TABLE market_business MODIFY latitude DECIMAL(12,10)');
            DB::statement('ALTER TABLE market_business MODIFY longitude DECIMAL(13,10)');
        });

        Schema::table('supplement_business', function (Blueprint $table) {
            // Use raw statements to change column types
            DB::statement('ALTER TABLE supplement_business MODIFY latitude DECIMAL(12,10)');
            DB::statement('ALTER TABLE supplement_business MODIFY longitude DECIMAL(13,10)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('market_business', function (Blueprint $table) {
            // Revert back to previous string types
            DB::statement('ALTER TABLE market_business MODIFY latitude VARCHAR(255)');
            DB::statement('ALTER TABLE market_business MODIFY longitude VARCHAR(255)');
        });

         Schema::table('supplement_business', function (Blueprint $table) {
            // Revert back to previous string types
            DB::statement('ALTER TABLE supplement_business MODIFY latitude VARCHAR(255)');
            DB::statement('ALTER TABLE supplement_business MODIFY longitude VARCHAR(255)');
        });
    }
};
