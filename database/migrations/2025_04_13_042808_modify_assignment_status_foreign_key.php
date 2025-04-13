<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('assignment_status', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['user_id']);

            // Re-add the foreign key with onDelete cascade
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('assignment_status', function (Blueprint $table) {
            // Drop the foreign key with cascade
            $table->dropForeign(['user_id']);

            // Re-add the original foreign key without cascade
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
    }
};
