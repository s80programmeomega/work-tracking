<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Rattachement direct d'une équipe à un workspace (indépendamment du projet lié).
            // Rétrocompatible : nullable pour les équipes sans projet existantes.
            $table->foreignId('workspace_id')
                ->nullable()
                ->after('project_id')
                ->constrained('workspaces')
                ->onDelete('cascade');

            $table->index(['workspace_id', 'is_active']);
        });

        // Remplissage : hériter le workspace_id depuis le projet lié pour les équipes existantes
        DB::statement(
            'UPDATE teams t
             INNER JOIN projets p ON p.id = t.project_id
             SET t.workspace_id = p.workspace_id
             WHERE t.project_id IS NOT NULL AND t.workspace_id IS NULL'
        );
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'workspace_id')) {
                $table->dropIndex(['workspace_id', 'is_active']);
                $table->dropForeign(['workspace_id']);
                $table->dropColumn('workspace_id');
            }
        });
    }
};
