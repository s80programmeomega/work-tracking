<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task 8c — track when the daily digest was last successfully sent to a user.
 *
 * The scheduled command (notifications:send-digest) reads this to figure out
 * which window of unread notifications to aggregate, and updates it after a
 * successful send. Indexed so the digest command can cheaply filter users
 * who are due for a digest.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->timestamp('last_digest_sent_at')->nullable()->after('digest_day_of_week');
            $table->index('last_digest_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            if (Schema::hasColumn('notification_preferences', 'last_digest_sent_at')) {
                $table->dropIndex(['last_digest_sent_at']);
                $table->dropColumn('last_digest_sent_at');
            }
        });
    }
};
