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
            $table->index('created_at');
            $table->index('match_level');
            $table->index('name');
            $table->index('address');
            $table->index('description');
            $table->index('note');
        });

        Schema::table('market_business', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('match_level');
            $table->index('name');
            $table->index('address');
            $table->index('description');
            $table->index('note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplement_business', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['match_level']);
            $table->dropIndex(['name']);
            $table->dropIndex(['address']);
            $table->dropIndex(['description']);
            $table->dropIndex(['note']);
        });

        Schema::table('market_business', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['match_level']);
            $table->dropIndex(['name']);
            $table->dropIndex(['address']);
            $table->dropIndex(['description']);
            $table->dropIndex(['note']);
        });
    }
};
