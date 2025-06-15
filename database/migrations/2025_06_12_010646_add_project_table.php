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
        Schema::table('market_business', function (Blueprint $table) {
            $table->index('latitude');
            $table->index('longitude');
        });

        Schema::table('supplement_business', function (Blueprint $table) {
            $table->index('latitude');
            $table->index('longitude');
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['swmaps supplement', 'kendedes mobile', 'swmaps market', 'wilkerstat', 'jenggala']);

            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('supplement_business', function (Blueprint $table) {
            $table->dropForeign(['upload_id']);
            $table->uuid('upload_id')->nullable()->change();
            $table->foreign('upload_id')
                ->references('id')
                ->on('supplement_upload_status');

            $table->uuid('project_id')->nullable();
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');

            $table->string('owner')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Remove indexes from market_business
        Schema::table('market_business', function (Blueprint $table) {
            $table->dropIndex(['latitude']);
            $table->dropIndex(['longitude']);
        });

        // Remove indexes from supplement_business
        Schema::table('supplement_business', function (Blueprint $table) {
            $table->dropIndex(['latitude']);
            $table->dropIndex(['longitude']);
        });

        // Drop foreign keys and project_id column from supplement_business
        Schema::table('supplement_business', function (Blueprint $table) {
            // Drop foreign key for project_id and remove the column
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');

            // Drop foreign key for upload_id
            $table->dropForeign(['upload_id']);

            // Change upload_id back to NOT NULL
            $table->uuid('upload_id')->nullable(true)->change();

            // Re-add the foreign key constraint
            $table->foreign('upload_id')
                ->references('id')
                ->on('supplement_upload_status');

            $table->dropColumn('owner');
        });

        // Drop the projects table
        Schema::dropIfExists('projects');
    }
};
