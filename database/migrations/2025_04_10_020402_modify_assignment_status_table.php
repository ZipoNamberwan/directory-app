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
        Schema::dropIfExists('market_assignment_status');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('market_assignment_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('status', ['start', 'loading', 'success', 'failed', 'success with error']);
            $table->foreignUuid('user_id')->constrained('users');
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }
};
