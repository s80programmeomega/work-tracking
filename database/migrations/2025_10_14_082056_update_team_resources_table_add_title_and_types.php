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
        Schema::table('team_resources', function (Blueprint $table) {
            // Add title column (can be same as name or different)
            $table->string('title')->nullable()->after('type');

            // Update enum type to include 'file' type
            DB::statement("ALTER TABLE team_resources MODIFY COLUMN type ENUM('template', 'document', 'checklist', 'link', 'note', 'file') DEFAULT 'document'");
        });

        // Copy existing 'name' values to 'title' if title is null
        DB::statement('UPDATE team_resources SET title = name WHERE title IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_resources', function (Blueprint $table) {
            $table->dropColumn('title');

            // Revert enum type
            DB::statement("ALTER TABLE team_resources MODIFY COLUMN type ENUM('template', 'document', 'checklist', 'link', 'note') DEFAULT 'document'");
        });
    }
};
