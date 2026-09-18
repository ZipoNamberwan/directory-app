<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicateCount = DB::table('enumeration_business')
            ->select('assignment_id')
            ->groupBy('assignment_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicateCount > 0) {
            throw new \RuntimeException(
                "Cannot add unique index: {$duplicateCount} duplicate assignment_id value(s) found in enumeration_business. Resolve duplicates before migrating."
            );
        }

        Schema::table('enumeration_business', function (Blueprint $table) {
            // Replaces the plain index from the original create-table migration —
            // a unique index already serves lookups, no need for both.
            $table->dropIndex(['assignment_id']);
            $table->unique('assignment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enumeration_business', function (Blueprint $table) {
            $table->dropUnique(['assignment_id']);
            $table->index('assignment_id');
        });
    }
};
