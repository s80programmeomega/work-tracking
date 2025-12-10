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
        Schema::table('tache_resultats', function (Blueprint $table) {
            // Vérifier si les colonnes n'existent pas déjà
            if (!Schema::hasColumn('tache_resultats', 'rejete_par')) {
                $table->unsignedBigInteger('rejete_par')->nullable()->after('validateur_n2_id');
                $table->foreign('rejete_par')->references('id')->on('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('tache_resultats', 'rejete_le')) {
                $table->timestamp('rejete_le')->nullable()->after('rejete_par');
            }

            if (!Schema::hasColumn('tache_resultats', 'motif_rejet')) {
                $table->text('motif_rejet')->nullable()->after('rejete_le');
            }

            if (!Schema::hasColumn('tache_resultats', 'niveau_rejet')) {
                $table->enum('niveau_rejet', ['n1', 'n2'])->nullable()->after('motif_rejet');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tache_resultats', function (Blueprint $table) {
            if (Schema::hasColumn('tache_resultats', 'rejete_par')) {
                $table->dropForeign(['rejete_par']);
                $table->dropColumn('rejete_par');
            }
            
            $table->dropColumn(['rejete_le', 'motif_rejet', 'niveau_rejet']);
        });
    }
};