<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE regencies
            ADD COLUMN geom MULTIPOLYGON SRID 4326 NULL
        ");

        DB::statement("
            ALTER TABLE subdistricts
            ADD COLUMN geom MULTIPOLYGON SRID 4326 NULL
        ");

        DB::statement("
            ALTER TABLE villages
            ADD COLUMN geom MULTIPOLYGON SRID 4326 NULL
        ");

        DB::statement("
            ALTER TABLE sls
            ADD COLUMN geom MULTIPOLYGON SRID 4326 NULL
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE regencies DROP COLUMN geom");
        DB::statement("ALTER TABLE subdistricts DROP COLUMN geom");
        DB::statement("ALTER TABLE villages DROP COLUMN geom");
        DB::statement("ALTER TABLE sls DROP COLUMN geom");
    }
};
