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
            $table->text('message')->nullable();
            $table->enum('status', ['start', 'loading', 'processing', 'success', 'failed', 'success with error']);
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('market_id')->constrained('markets');
            $table->string('regency_id');
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->string('user_firstname');
            $table->string('market_name');
            $table->string('regency_name');
            $table->timestamps();
        });

        Schema::create('market_assignment_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('status', ['start', 'loading', 'success', 'failed', 'success with error']);
            $table->foreignUuid('user_id')->constrained('users');
            $table->text('message')->nullable();
            $table->timestamps();
        });

        Schema::table('market_business', function (Blueprint $table) {
            $table->foreignUuid('upload_id')->constrained('market_upload_status');
        });

        Schema::create('market_user', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('market_id')->constrained()->onDelete('cascade');
            $table->string('user_firstname');
            $table->string('market_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('market_business', function (Blueprint $table) {
            $table->dropForeign(['upload_id']);
            $table->dropColumn('upload_id');
        });

        Schema::dropIfExists('market_upload_status');
        Schema::dropIfExists('market_assignment_status');
        Schema::dropIfExists('market_user');
    }
};
