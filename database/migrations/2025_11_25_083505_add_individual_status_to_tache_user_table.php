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
         Schema::table('tache_user', function (Blueprint $table) {
            // Statut individuel de chaque assigné
            $table->string('statut_individuel')
                ->default('a_faire')
                ->after('can_validate')
                ->comment('Statut personnel: a_faire, en_cours, termine');
            
            // Progression individuelle (0-100)
            $table->integer('progression_individuelle')
                ->default(0)
                ->after('statut_individuel')
                ->comment('Progression personnelle en pourcentage');
            
            // Timestamps pour tracking individuel
            $table->timestamp('started_at')
                ->nullable()
                ->after('progression_individuelle')
                ->comment('Date de début personnel');
            
            $table->timestamp('completed_at')
                ->nullable()
                ->after('started_at')
                ->comment('Date de complétion personnelle');
            
            // Notes personnelles (optionnel)
            $table->text('notes_personnelles')
                ->nullable()
                ->after('completed_at')
                ->comment('Notes privées de l\'assigné');
        });


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
            //
        });
        
        Schema::table('tache_resultats', function (Blueprint $table) {
            $table->dropUnique('unique_tache_user_resultat');
            $table->dropColumn('is_individual');
        });
    }
};
