<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tache_user', function (Blueprint $table) {
            // Statut individuel de chaque assigné
            if (!Schema::hasColumn('tache_user', 'statut_individuel')) {
                $table->enum('statut_individuel', ['a_faire', 'en_cours', 'termine'])
                    ->default('a_faire')
                    ->after('can_validate')
                    ->comment('Statut individuel de l\'utilisateur pour cette tâche');
            }

            // Progression individuelle (0-100)
            if (!Schema::hasColumn('tache_user', 'progression_individuelle')) {
                $table->integer('progression_individuelle')
                    ->default(0)
                    ->after('statut_individuel')
                    ->comment('Progression individuelle en pourcentage (0-100)');
            }

            // Timestamps pour tracking individuel
            if (!Schema::hasColumn('tache_user', 'started_at')) {
                $table->timestamp('started_at')
                    ->nullable()
                    ->after('progression_individuelle')
                    ->comment('Date de début de travail sur la tâche');
            }

           if (!Schema::hasColumn('tache_user', 'completed_at')) {
                $table->timestamp('completed_at')
                    ->nullable()
                    ->after('started_at')
                    ->comment('Date de complétion de la tâche par cet utilisateur');
            }

            // Notes personnelles (optionnel)
            if (!Schema::hasColumn('tache_user', 'notes_personnelles')) {
                $table->text('notes_personnelles')
                    ->nullable()
                    ->after('completed_at')
                    ->comment('Notes personnelles de l\'utilisateur sur cette tâche');
            }
        });

        
        // ✅ Initialiser les statuts individuels existants avec le statut global
        DB::statement("
            UPDATE tache_user tu
            INNER JOIN taches t ON t.id = tu.tache_id
            SET tu.statut_individuel = t.statut
            WHERE tu.statut_individuel = 'a_faire'
        ");


        Schema::table('tache_resultats', function (Blueprint $table) {
            // ✅ S'assurer que user_id existe et est bien indexé
            if (!Schema::hasColumn('tache_resultats', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('tache_id')
                    ->constrained('users')
                    ->onDelete('cascade')
                    ->comment('Utilisateur qui a soumis le résultat');
            }

            // ✅ Ajouter index unique pour éviter les doublons
            // Un utilisateur = un résultat par tâche
            $table->unique(['tache_id', 'user_id'], 'unique_tache_user_resultat');

            // ✅ Ajouter flag pour différencier résultat global vs individuel
            $table->boolean('is_individual')
                ->default(true)
                ->after('user_id')
                ->comment('true = résultat individuel, false = résultat global de la tâche');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('tache_user', function (Blueprint $table) {
            $table->dropColumn([
                'statut_individuel',
                'progression_individuelle',
                'started_at',
                'completed_at',
                'notes_personnelles'
            ]);
        });

        Schema::table('tache_resultats', function (Blueprint $table) {
            $table->dropUnique('unique_tache_user_resultat');
            $table->dropColumn('is_individual');
        });
    }
};
