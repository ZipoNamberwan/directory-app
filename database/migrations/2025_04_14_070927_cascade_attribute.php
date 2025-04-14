<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('report_market_business_user', function (Blueprint $table) {
            // First, drop the existing foreign key
            $table->dropForeign(['user_id']);

            // Then add the new one with ON DELETE CASCADE
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });

        Schema::table('report_market_business_market', function (Blueprint $table) {
            // Drop the existing foreign key on market_id
            $table->dropForeign(['market_id']);

            // Add the new foreign key with cascade on delete
            $table->foreign('market_id')
                ->references('id')->on('markets')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('report_market_business_user', function (Blueprint $table) {
            // Revert to the original constraint
            $table->dropForeign(['user_id']);

            $table->foreign('user_id')
                ->references('id')->on('users'); // No cascade
        });

        Schema::table('report_market_business_market', function (Blueprint $table) {
            // Drop the cascading foreign key
            $table->dropForeign(['market_id']);

            // Revert to original FK without cascade
            $table->foreign('market_id')
                ->references('id')->on('markets');
        });
    }
};
