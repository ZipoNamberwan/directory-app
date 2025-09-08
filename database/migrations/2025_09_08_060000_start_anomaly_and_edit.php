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
            $table->boolean('is_locked')->default(false);
        });

        Schema::table('market_business', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false);
        });

        Schema::create('anomaly_types', function (Blueprint $table) {
            $table->id()->autoincrement();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->text('column');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('anomaly_repairs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('business_id');
            $table->string('business_type');

            $table->enum('status', ['notconfirmed', 'dismissed', 'fixed', 'deleted', 'moved'])->default('notconfirmed');

            $table->foreignId('anomaly_type_id')->constrained('anomaly_types');

            $table->string('old_value')->nullable();
            $table->string('fixed_value')->nullable();
            $table->string('note')->nullable();

            $table->uuid('last_repaired_by')->nullable();
            $table->foreign('last_repaired_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            $table->timestamp('repaired_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'business_type']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplement_business', function (Blueprint $table) {
            $table->dropColumn(['is_locked']);
        });
        Schema::table('market_business', function (Blueprint $table) {
            $table->dropColumn(['is_locked']);
        });
        Schema::dropIfExists('anomaly_repairs');
        Schema::dropIfExists('anomaly_types');
    }
};
