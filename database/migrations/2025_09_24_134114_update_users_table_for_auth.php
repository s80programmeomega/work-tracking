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
        Schema::table('users', function (Blueprint $table) {
            // Add new columns for Work Tracking application
            $table->string('nom')->after('id')->nullable();
            $table->enum('role', ['super_admin', 'manager', 'member','viewer', 'admin', 'cadre', 'stagiaire'])
                ->default('admin')->after('password');
            $table->string('fonction')->nullable()->after('role');
            $table->string('avatar')->nullable()->after('fonction');

            // Drop the default 'name' column if it exists
            if (Schema::hasColumn('users', 'name')) {
                $table->dropColumn('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Restore original structure
            $table->string('name')->after('id');

            // Drop new columns
            $table->dropColumn(['nom', 'role', 'fonction', 'avatar']);
        });
    }
};
