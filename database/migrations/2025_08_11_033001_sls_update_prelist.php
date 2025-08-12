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
        Schema::create('sls_update_prelist', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sls_id');
            $table->foreign('sls_id')->references('id')->on('sls');
            $table->boolean('has_been_downloaded')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sls_update_prelist');
    }
};
