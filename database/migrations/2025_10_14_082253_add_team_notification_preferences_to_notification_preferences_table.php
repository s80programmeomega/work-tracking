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
        Schema::table('notification_preferences', function (Blueprint $table) {
            // Team-related notification preferences
            $table->boolean('team_announcements')->default(true)->after('document_uploaded_push');
            $table->boolean('team_events')->default(true)->after('team_announcements');
            $table->boolean('team_resources')->default(true)->after('team_events');
            $table->boolean('team_member_added')->default(true)->after('team_resources');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'team_announcements',
                'team_events',
                'team_resources',
                'team_member_added',
            ]);
        });
    }
};
