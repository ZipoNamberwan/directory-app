<?php

use App\Helpers\DatabaseSelector;
use App\Models\Regency;
use App\Models\Sls;
use App\Models\Subdistrict;
use App\Models\Village;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('regencies', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        Schema::table('subdistricts', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        Schema::table('villages', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        Schema::table('sls', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // $isDefaultConnection = DB::getDefaultConnection() == DatabaseSelector::getDefaultConnection();
        // $regencies = Regency::all();
        // foreach ($regencies as $regency) {
        //     if ($isDefaultConnection) {
        //         $regency->uuid = (string) Str::uuid();
        //         $regency->save();
        //     } else {
        //         $regency->uuid = Regency::on(DatabaseSelector::getDefaultConnection())->find($regency->id)->uuid;
        //         $regency->save();
        //     }
        // }

        // $subdistricts = Subdistrict::all();
        // foreach ($subdistricts as $subdistrict) {
        //     if ($isDefaultConnection) {
        //         $subdistrict->uuid = (string) Str::uuid();
        //         $subdistrict->save();
        //     } else {
        //         $subdistrict->uuid = Subdistrict::on(DatabaseSelector::getDefaultConnection())->find($subdistrict->id)->uuid;
        //         $subdistrict->save();
        //     }
        // }

        // $villages = Village::all();
        // foreach ($villages as $village) {
        //     if ($isDefaultConnection) {
        //         $village->uuid = (string) Str::uuid();
        //         $village->save();
        //     } else {
        //         $village->uuid = Village::on(DatabaseSelector::getDefaultConnection())->find($village->id)->uuid;
        //         $village->save();
        //     }
        // }

        // $slss = Sls::all();
        // foreach ($slss as $sls) {
        //     if ($isDefaultConnection) {
        //         $sls->uuid = (string) Str::uuid();
        //         $sls->save();
        //     } else {
        //         $sls->uuid = Sls::on(DatabaseSelector::getDefaultConnection())->find($sls->id)->uuid;
        //         $sls->save();
        //     }
        // }


        $sourceConnection = DatabaseSelector::getDefaultConnection();
        $targetConnection = DB::getDefaultConnection();

        $tables = [
            'regencies',
            'subdistricts',
            'villages',
            'sls',
        ];

        /*
         |---------------------------------------------------------
         | CASE 1: DEFAULT DB → generate UUID
         |---------------------------------------------------------
         */
        if ($sourceConnection === $targetConnection) {
            foreach ($tables as $table) {
                DB::connection($sourceConnection)->statement("
                    UPDATE {$table}
                    SET uuid = UUID()
                    WHERE uuid IS NULL
                ");
            }

            return;
        }

        /*
         |---------------------------------------------------------
         | CASE 2: NON-DEFAULT DB → sync UUID from SOURCE DB
         |---------------------------------------------------------
         */
        foreach ($tables as $table) {

            DB::connection($sourceConnection)
                ->table($table)
                ->select('id', 'uuid')
                ->whereNotNull('uuid')
                ->orderBy('id')
                ->chunk(2000, function ($rows) use ($table, $targetConnection) {

                    // Build explicit mapping: id => uuid
                    $map = [];
                    foreach ($rows as $row) {
                        $map[$row->id] = $row->uuid;
                    }

                    if (empty($map)) {
                        return;
                    }

                    /*
                     |---------------------------------------------
                     | BULK UPDATE USING CASE (1 QUERY / CHUNK)
                     |---------------------------------------------
                     */
                    $cases = '';
                    $ids   = [];

                    foreach ($map as $id => $uuid) {
                        $cases .= "WHEN {$id} THEN '{$uuid}' ";
                        $ids[] = $id;
                    }

                    $ids = implode(',', $ids);

                    DB::connection($targetConnection)->statement("
                        UPDATE {$table}
                        SET uuid = CASE id
                            {$cases}
                        END
                        WHERE id IN ({$ids})
                    ");
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regencies', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('subdistricts', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('villages', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('sls', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
