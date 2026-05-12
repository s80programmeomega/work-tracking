<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // workspace_members: owner stays, replace admin/member/viewer
        DB::statement("ALTER TABLE workspace_members MODIFY COLUMN role ENUM('owner','manager','cadre','collaborateur','stagiaire','observateur') NOT NULL DEFAULT 'collaborateur'");

        // projet_user: owner stays, replace admin/member/viewer
        DB::statement("ALTER TABLE projet_user MODIFY COLUMN role ENUM('owner','manager','cadre','collaborateur','stagiaire','observateur') NOT NULL DEFAULT 'collaborateur'");

        // activite_user: replace responsable/collaborator/viewer
        DB::statement("ALTER TABLE activite_user MODIFY COLUMN role ENUM('cadre','collaborateur','stagiaire','observateur') NOT NULL DEFAULT 'collaborateur'");

        // tache_user: simplify role, add is_responsable flag
        DB::statement("ALTER TABLE tache_user MODIFY COLUMN role ENUM('collaborateur','stagiaire','observateur') NOT NULL DEFAULT 'collaborateur'");

        Schema::table('tache_user', function (Blueprint $table) {
            $table->boolean('is_responsable')->default(false)->after('role');
        });

        // Migrate existing data
        DB::statement("UPDATE workspace_members SET role = 'manager' WHERE role = 'admin'");
        DB::statement("UPDATE workspace_members SET role = 'collaborateur' WHERE role = 'member'");
        DB::statement("UPDATE workspace_members SET role = 'observateur' WHERE role = 'viewer'");

        DB::statement("UPDATE projet_user SET role = 'manager' WHERE role = 'admin'");
        DB::statement("UPDATE projet_user SET role = 'collaborateur' WHERE role = 'member'");
        DB::statement("UPDATE projet_user SET role = 'observateur' WHERE role = 'viewer'");

        DB::statement("UPDATE activite_user SET role = 'cadre' WHERE role = 'responsable'");
        DB::statement("UPDATE activite_user SET role = 'collaborateur' WHERE role = 'collaborator'");
        DB::statement("UPDATE activite_user SET role = 'observateur' WHERE role = 'viewer'");

        DB::statement("UPDATE tache_user SET is_responsable = 1 WHERE role = 'responsable'");
        DB::statement("UPDATE tache_user SET role = 'collaborateur' WHERE role IN ('assignee','responsable','collaborator')");
        DB::statement("UPDATE tache_user SET role = 'observateur' WHERE role IN ('observer','validator')");
    }

    public function down(): void
    {
        Schema::table('tache_user', function (Blueprint $table) {
            $table->dropColumn('is_responsable');
        });

        DB::statement("ALTER TABLE tache_user MODIFY COLUMN role ENUM('assignee','validator','observer','responsable','collaborator') NOT NULL DEFAULT 'assignee'");
        DB::statement("ALTER TABLE activite_user MODIFY COLUMN role ENUM('responsable','collaborator','viewer') NOT NULL DEFAULT 'collaborator'");
        DB::statement("ALTER TABLE projet_user MODIFY COLUMN role ENUM('owner','admin','member','viewer') NOT NULL DEFAULT 'member'");
        DB::statement("ALTER TABLE workspace_members MODIFY COLUMN role ENUM('owner','admin','member','viewer') NOT NULL DEFAULT 'member'");
    }
};
