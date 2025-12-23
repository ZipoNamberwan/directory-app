<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE assignment_status
            MODIFY COLUMN `type` ENUM(
                'export',
                'import',
                'download-sls-business',
                'download-non-sls-business',
                'import-business',
                'upload-market-assignment',
                'download-market-raw',
                'download-market-master',
                'download-supplement-business',
                'dashboard-regency',
                'dashboard-user',
                'dashboard-market',
                'dashboard-supplement',
                'download-anomaly',
                'dashboard-area',
                'download-duplicate'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE assignment_status
            MODIFY COLUMN `type` ENUM(
                'export',
                'import',
                'download-sls-business',
                'download-non-sls-business',
                'import-business',
                'upload-market-assignment',
                'download-market-raw',
                'download-market-master',
                'download-supplement-business',
                'dashboard-regency',
                'dashboard-user',
                'dashboard-market',
                'dashboard-supplement',
                'download-anomaly',
                'dashboard-area'
            ) NOT NULL
        ");
    }
};
