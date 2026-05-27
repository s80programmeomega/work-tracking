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
        Schema::table('workspaces', function (Blueprint $table) {
            $table->string('subscription_mode')->default('trial')->after('is_active'); // trial | paid
            $table->timestamp('trial_started_at')->nullable()->after('subscription_mode');
            $table->unsignedSmallInteger('trial_duration_days')->default(30)->after('trial_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            if (Schema::hasColumn('workspaces', 'subscription_mode')) {
                $table->dropColumn(['subscription_mode', 'trial_started_at', 'trial_duration_days']);
            }
        });
    }
};
