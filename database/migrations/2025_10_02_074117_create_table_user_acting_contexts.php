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
        Schema::create('user_acting_contexts', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->nullable();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->string('acting_org_id');
            $table->foreign('acting_org_id')->references('id')->on('organizations');
            $table->string('acting_role');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['active', 'acting_org_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_acting_contexts');
    }
};
