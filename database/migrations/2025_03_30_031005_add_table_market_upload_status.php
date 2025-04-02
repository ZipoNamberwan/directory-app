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
        Schema::create('market_upload_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('filename');
            $table->enum('status', ['start', 'loading', 'processing', 'success', 'failed', 'success with error']);
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('market_id')->constrained('markets');
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
