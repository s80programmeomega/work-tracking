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
        Schema::table('taches', function (Blueprint $table) {
            // Add unique code field (TASK-0001 format)
            $table->string('code')->unique()->nullable()->after('titre');

            // Add archive fields
            $table->enum('archive_status', ['active', 'archived'])->default('active')->after('metadata');
            $table->timestamp('archived_at')->nullable()->after('archive_status');

            // Add index for archive_status
            $table->index('archive_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->dropColumn(['code', 'archive_status', 'archived_at']);
        });
    }
};
