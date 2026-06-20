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
        Schema::create('infos', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('title');
            $table->text('subtitle')->nullable();
            $table->text('tags')->nullable();

            $table->enum('type', [
                'announcement',
                'faq',
                'other'
            ]);

            $table->longText('content');

            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('infos');
    }
};
