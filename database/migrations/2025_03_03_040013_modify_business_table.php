<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sls_business', function (Blueprint $table) {
            $table->string('initial_name')->nullable()->after('name');
            $table->string('initial_owner')->nullable()->after('owner');
        });
        Schema::table('non_sls_business', function (Blueprint $table) {
            $table->string('initial_name')->nullable()->after('name');
            $table->string('initial_owner')->nullable()->after('owner');
        });

        DB::table('sls_business')->update([
            'initial_name' => DB::raw('name'),
            'initial_owner' => DB::raw('owner'),
        ]);

        DB::table('non_sls_business')->update([
            'initial_name' => DB::raw('name'),
            'initial_owner' => DB::raw('owner'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sls_business', function (Blueprint $table) {
            $table->dropColumn(['initial_name', 'initial_owner']);
        });

        Schema::table('non_sls_business', function (Blueprint $table) {
            $table->dropColumn(['initial_name', 'initial_owner']);
        });
    }
};
