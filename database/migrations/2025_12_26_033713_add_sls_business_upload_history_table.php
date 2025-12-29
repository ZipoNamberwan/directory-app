<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sls_business_upload_history', function (Blueprint $table) {
            $table->id();
            $table->string('sls_id');
            $table->integer('total');
            $table->text('chief_name');
            $table->text('chief_phone');
            $table->decimal('chief_latitude', 12, 10);
            $table->decimal('chief_longitude', 13, 10);
            $table->uuid('user_id')->nullable();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sls_business_upload_history');
    }
};
