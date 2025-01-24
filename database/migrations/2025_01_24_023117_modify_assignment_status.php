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
        Schema::table('assignment_status', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->foreignId('user_id')->constrained('users');
        });

        Schema::rename('assignment_status', 'export_assignment_status');

        Schema::create('import_assignment_status', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->enum('status', ['start', 'loading', 'success', 'failed']);
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_assignment_status');

        Schema::rename('export_assignment_status', 'assignment_status');

        Schema::table('assignment_status', function (Blueprint $table) {
            $table->dropForeign(['user_id']); 
            $table->dropColumn('user_id');
            $table->string('type'); 
        });
    }
};
