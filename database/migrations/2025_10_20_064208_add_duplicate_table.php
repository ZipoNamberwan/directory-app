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
        Schema::table('supplement_business', function (Blueprint $table) {
            $table->dropColumn('is_etl');
            $table->dateTime('duplicate_scan_at')->nullable()->after('checked_at');
        });

        Schema::table('market_business', function (Blueprint $table) {
            $table->dropColumn('is_etl');
            $table->dateTime('duplicate_scan_at')->nullable()->after('checked_at');
        });

        Schema::create('duplicate_candidates', function (Blueprint $table) {
            $table->uuid('id')->primary();

            //morph to supplement business and market business tables
            $table->uuid('center_business_id');
            $table->string('center_business_type');

            //morph to supplement business and market business tables
            $table->uuid('nearby_business_id');
            $table->string('nearby_business_type');

            $table->string('center_business_name');
            $table->string('nearby_business_name');

            $table->string('center_business_owner');
            $table->string('nearby_business_owner');

            $table->double('name_similarity');
            $table->double('owner_similarity');
            $table->double('confidence_score');
            $table->double('distance_meters');

            $table->string('duplicate_status')->nullable();

            $table->decimal('center_business_latitude', 12, 10);
            $table->decimal('center_business_longitude', 13, 10);
            $table->decimal('nearby_business_latitude', 12, 10);
            $table->decimal('nearby_business_longitude', 13, 10);

            $table->enum('status', ['notconfirmed', 'keep1', 'keep2', 'keepall'])->default('notconfirmed');

            $table->uuid('last_confirmed_by')->nullable();
            $table->foreign('last_confirmed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplement_business', function (Blueprint $table) {
            $table->boolean('is_etl')->default(false);
            $table->dropColumn(['duplicate_scan_at']);
        });

        Schema::table('market_business', function (Blueprint $table) {
            $table->boolean('is_etl')->default(false);
            $table->dropColumn(['duplicate_scan_at']);
        });

        Schema::dropIfExists('duplicate_candidates');
    }
};
