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
            // Add bio if it doesn't exist
            if (!Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('avatar');
            }

            // Add adresse if it doesn't exist
            if (!Schema::hasColumn('users', 'adresse')) {
                $table->string('adresse')->nullable()->after('bio');
            }

            // Add language if it doesn't exist
            if (!Schema::hasColumn('users', 'language')) {
                $table->string('language', 10)->default('fr')->after('adresse');
            }

            // Add timezone if it doesn't exist
            if (!Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone', 50)->default('UTC')->after('language');
            }

            // Add notification_preferences if it doesn't exist
            if (!Schema::hasColumn('users', 'notification_preferences')) {
                $table->json('notification_preferences')->nullable()->after('timezone');
            }

            // Add indexes
            $table->index('role');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bio', 'adresse', 'language', 'timezone', 'notification_preferences']);
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
        });
    }
};
