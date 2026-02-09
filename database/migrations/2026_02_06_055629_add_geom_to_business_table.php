<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = ['supplement_business', 'market_business'];
        $chunk = 5000;

        foreach ($tables as $table) {
            DB::statement(
                "ALTER TABLE {$table}
             ADD COLUMN coordinate POINT SRID 4326 NULL"
            );
            DB::table($table)
                ->whereNull('coordinate')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->chunkById($chunk, function ($rows) use ($table) {
                    DB::transaction(function () use ($rows, $table) {
                        foreach ($rows as $row) {
                            DB::statement(
                                "UPDATE {$table}
                             SET coordinate = ST_SRID(POINT(?, ?), 4326)
                             WHERE id = ?",
                                [$row->longitude, $row->latitude, $row->id]
                            );
                        }
                    });
                });

            DB::statement(
                "UPDATE {$table}
             SET coordinate = ST_SRID(POINT(0, 0), 4326)
             WHERE coordinate IS NULL"
            );

            DB::statement(
                "ALTER TABLE {$table}
             MODIFY COLUMN coordinate POINT SRID 4326 NOT NULL"
            );

            DB::statement(
                "ALTER TABLE {$table}
             ADD SPATIAL INDEX idx_{$table}_coordinate (coordinate)"
            );
        }
    }

    public function down(): void
    {
        $tables = ['supplement_business', 'market_business'];

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} DROP INDEX idx_{$table}_coordinate");
        }

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} DROP COLUMN coordinate");
        }
    }
};
