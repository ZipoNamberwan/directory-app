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
        Schema::create('kbli_statistics', function (Blueprint $table) {
            $table->id();

            $table->uuid('area_id')->index();
            $table->string('area_type')->index();
            
            $table->string('category')->nullable();
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->integer('count')->default(0);

            $table->index(['area_type', 'area_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kbli_statistics');
    }
};
