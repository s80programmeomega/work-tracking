<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replace role ENUM string columns in all pivot tables with role_id FK → roles.id.
 *
 * Why: Makes role→permission mappings fully DB-driven and editable from the admin UI
 * at runtime via Spatie's role_has_permissions table. ContextualPermissionGate resolves
 * permissions by loading the Spatie role for each pivot row.
 *
 * IMPORTANT: Run RolePermissionSeeder BEFORE this migration in fresh installs
 * (roles must exist before we can populate role_id). For existing DBs, this migration
 * populates role_id from the existing role string values.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── workspace_members ─────────────────────────────────────────────
        Schema::table('workspace_members', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('user_id');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');
        });

        DB::statement('
            UPDATE workspace_members wm
            INNER JOIN roles r ON r.name = wm.role AND r.guard_name = "web"
            SET wm.role_id = r.id
        ');

        // Safe fallback for any unmapped rows
        DB::statement('
            UPDATE workspace_members wm
            INNER JOIN roles r ON r.name = "observateur" AND r.guard_name = "web"
            SET wm.role_id = r.id
            WHERE wm.role_id IS NULL
        ');

        Schema::table('workspace_members', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
            $table->dropColumn('role');
            if (Schema::hasColumn('workspace_members', 'permissions')) {
                $table->dropColumn('permissions');
            }
        });

        // ── projet_user ───────────────────────────────────────────────────
        Schema::table('projet_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('user_id');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');
        });

        DB::statement('
            UPDATE projet_user pu
            INNER JOIN roles r ON r.name = pu.role AND r.guard_name = "web"
            SET pu.role_id = r.id
        ');

        DB::statement('
            UPDATE projet_user pu
            INNER JOIN roles r ON r.name = "observateur" AND r.guard_name = "web"
            SET pu.role_id = r.id
            WHERE pu.role_id IS NULL
        ');

        Schema::table('projet_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
            $table->dropColumn('role');
        });

        // ── activite_user ─────────────────────────────────────────────────
        Schema::table('activite_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('user_id');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');
        });

        DB::statement('
            UPDATE activite_user au
            INNER JOIN roles r ON r.name = au.role AND r.guard_name = "web"
            SET au.role_id = r.id
        ');

        DB::statement('
            UPDATE activite_user au
            INNER JOIN roles r ON r.name = "observateur" AND r.guard_name = "web"
            SET au.role_id = r.id
            WHERE au.role_id IS NULL
        ');

        Schema::table('activite_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
            $table->dropColumn('role');
        });

        // ── tache_user ────────────────────────────────────────────────────
        Schema::table('tache_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('user_id');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');
        });

        DB::statement('
            UPDATE tache_user tu
            INNER JOIN roles r ON r.name = tu.role AND r.guard_name = "web"
            SET tu.role_id = r.id
        ');

        // tache_user roles: collaborateur/stagiaire/observateur — default to collaborateur
        DB::statement('
            UPDATE tache_user tu
            INNER JOIN roles r ON r.name = "collaborateur" AND r.guard_name = "web"
            SET tu.role_id = r.id
            WHERE tu.role_id IS NULL
        ');

        Schema::table('tache_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        // Restore tache_user.role
        Schema::table('tache_user', function (Blueprint $table) {
            $table->string('role')->default('collaborateur')->after('user_id');
        });
        DB::statement('UPDATE tache_user tu INNER JOIN roles r ON r.id = tu.role_id SET tu.role = r.name');
        Schema::table('tache_user', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        // Restore activite_user.role
        Schema::table('activite_user', function (Blueprint $table) {
            $table->string('role')->default('collaborateur')->after('user_id');
        });
        DB::statement('UPDATE activite_user au INNER JOIN roles r ON r.id = au.role_id SET au.role = r.name');
        Schema::table('activite_user', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        // Restore projet_user.role
        Schema::table('projet_user', function (Blueprint $table) {
            $table->string('role')->default('collaborateur')->after('user_id');
        });
        DB::statement('UPDATE projet_user pu INNER JOIN roles r ON r.id = pu.role_id SET pu.role = r.name');
        Schema::table('projet_user', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        // Restore workspace_members.role + permissions
        Schema::table('workspace_members', function (Blueprint $table) {
            $table->string('role')->default('collaborateur')->after('user_id');
            $table->json('permissions')->nullable();
        });
        DB::statement('UPDATE workspace_members wm INNER JOIN roles r ON r.id = wm.role_id SET wm.role = r.name');
        Schema::table('workspace_members', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
