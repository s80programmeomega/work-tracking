<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporary_access', function (Blueprint $table) {
            // Permissions personnalisées accordées au-delà du rôle de base.
            // NULL = utiliser les permissions du rôle par défaut.
            $table->json('custom_permissions')->nullable()->after('role');
        });

        Schema::table('workspace_members', function (Blueprint $table) {
            // Copie des permissions personnalisées au moment de la provision du membership.
            $table->json('custom_permissions')->nullable()->after('is_temp_access');
        });
    }

    public function down(): void
    {
        Schema::table('temporary_access', function (Blueprint $table) {
            $table->dropColumn('custom_permissions');
        });

        Schema::table('workspace_members', function (Blueprint $table) {
            if (Schema::hasColumn('workspace_members', 'custom_permissions')) {
                $table->dropColumn('custom_permissions');
            }
        });
    }
};
