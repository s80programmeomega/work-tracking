<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('admin_expires_at')->nullable()->after('is_super_admin');
            $table->enum('admin_expiry_action', ['suspend', 'delete'])->default('suspend')->after('admin_expires_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('admin_expiry_action');
            // NULL = not the system owner; TRUE (1) = system owner.
            // The unique index below enforces only one TRUE value can exist across all rows.
            $table->boolean('is_system_owner')->nullable()->default(null)->after('created_by');
        });

        // Ensure existing rows have NULL (not 0) so the unique index only fires for TRUE.
        DB::table('users')->update(['is_system_owner' => null]);
        DB::statement('CREATE UNIQUE INDEX users_system_owner_unique ON users (is_system_owner)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_system_owner_unique ON users');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['admin_expires_at', 'admin_expiry_action', 'created_by', 'is_system_owner']);
        });
    }
};
