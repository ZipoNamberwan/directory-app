<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE assignment_status MODIFY COLUMN `type` ENUM(
            'export',
            'import',
            'download-sls-business',
            'download-non-sls-business',
            'import-business',
            'upload-market-assignment',
            'download-market-raw',
            'download-market-master',
            'download-supplement-business'
        )");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE assignment_status MODIFY COLUMN `type` ENUM(
            'export',
            'import',
            'download-sls-business',
            'download-non-sls-business',
            'import-business',
            'upload-market-assignment',
            'download-market-raw'
        )");
    }
};
