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
        Schema::table('user_acting_contexts', function (Blueprint $table) {
            $table->string('acting_reg_id')->nullable()->after('acting_org_id');
            $table->foreign('acting_reg_id')->references('id')->on('regencies');

            $table->index(['acting_reg_id']);
        });

        DB::statement('UPDATE user_acting_contexts SET acting_reg_id = acting_org_id');

    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('user_acting_contexts', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['acting_reg_id']);

            // Drop index
            $table->dropIndex(['acting_reg_id']);

            // Drop the column
            $table->dropColumn('acting_reg_id');
        });
    }
};
