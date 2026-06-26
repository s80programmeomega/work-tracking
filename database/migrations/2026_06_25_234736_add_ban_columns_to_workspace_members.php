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
        Schema::table('workspace_members', function (Blueprint $table) {
            $table->timestamp('banned_at')->nullable()->after('invited_by');
            $table->foreignId('banned_by')->nullable()->constrained('users')->onDelete('set null')->after('banned_at');
            $table->string('ban_reason')->nullable()->after('banned_by');
        });
    }

    public function down(): void
    {
        Schema::table('workspace_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('banned_by');
            $table->dropColumn(['banned_at', 'ban_reason']);
        });
    }
};
