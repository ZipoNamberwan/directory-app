<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE infos MODIFY COLUMN type ENUM('announcement', 'faq', 'problem-solution', 'other') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE infos MODIFY COLUMN type ENUM('announcement', 'faq', 'other') NOT NULL");
    }
};
