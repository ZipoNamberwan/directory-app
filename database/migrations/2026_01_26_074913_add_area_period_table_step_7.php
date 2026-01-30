<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\DatabaseSelector;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('area_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');                 // e.g. "2025 Semester 1"
            $table->unsignedInteger('period_version'); // 1, 2, 3, ...

            $table->boolean('is_active')->default(false);

            $table->timestamps();

            // Prevent duplicate versions
            $table->unique('period_version');
        });

        $sourceConnection = DatabaseSelector::getDefaultConnection();
        $targetConnection = DB::getDefaultConnection();

        $areaPeriodId = (string) Str::uuid();

        $data = [
            'id'             => $areaPeriodId,
            'name'           => 'Semester 1 2024',
            'period_version' => 1,
            'is_active'      => true,
            'created_at'     => now(),
            'updated_at'     => now(),
        ];

        if ($sourceConnection === $targetConnection) {

            // ✅ Single DB
            DB::connection($targetConnection)
                ->table('area_periods')
                ->insert($data);

        } else {

            $uuid = DB::connection($sourceConnection)
                ->table('area_periods')
                ->first()->id;
            $data['id'] = $uuid;
            
            DB::connection($targetConnection)
                ->table('area_periods')
                ->insert($data);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_periods');
    }
};
