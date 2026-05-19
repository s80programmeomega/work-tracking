<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tache_resultats', function (Blueprint $table) {
            $table->boolean('bypass_active')->default(false)->after('action_n0_le');
            $table->text('motif_bypass')->nullable()->after('bypass_active');
            $table->timestamp('bypass_le')->nullable()->after('motif_bypass');
            $table->unsignedInteger('bypass_count')->default(0)->after('bypass_le');
        });

        Schema::table('tache_user', function (Blueprint $table) {
            $table->boolean('escalades_abusives')->default(false)->after('is_responsable');
        });
    }

    public function down(): void
    {
        Schema::table('tache_user', function (Blueprint $table) {
            if (Schema::hasColumn('tache_user', 'escalades_abusives')) {
                $table->dropColumn('escalades_abusives');
            }
        });

        Schema::table('tache_resultats', function (Blueprint $table) {
            $table->dropColumn(['bypass_active', 'motif_bypass', 'bypass_le', 'bypass_count']);
        });
    }
};
