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
        Schema::create('assignment_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('status', ['start', 'loading', 'success', 'failed', 'success with error']);
            $table->enum('type', ['export', 'import', 'download-sls-business', 'download-non-sls-business', 'import-business']);
            $table->foreignUuid('user_id')->constrained('users');
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_status');
    }
};
