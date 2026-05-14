<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate any existing self-referential subtasks into sous_taches before dropping the column
        $subtasks = DB::table('taches')->whereNotNull('parent_tache_id')->get();
        foreach ($subtasks as $subtask) {
            DB::table('sous_taches')->insert([
                'tache_id' => $subtask->parent_tache_id,
                'titre' => $subtask->titre,
                'description' => $subtask->description,
                'statut' => in_array($subtask->statut, ['a_faire', 'en_cours', 'termine', 'annule'])
                    ? $subtask->statut
                    : 'a_faire',
                'progression' => $subtask->taux_realisation ?? 0,
                'ordre' => $subtask->position ?? 0,
                'created_at' => $subtask->created_at,
                'updated_at' => $subtask->updated_at,
            ]);
        }

        Schema::table('taches', function (Blueprint $table) {
            $table->dropForeign(['parent_tache_id']);
            $table->dropIndex(['parent_tache_id']);
            $table->dropColumn('parent_tache_id');
        });
    }

    public function down(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->foreignId('parent_tache_id')->nullable()->after('activite_id')->constrained('taches')->cascadeOnDelete();
            $table->index('parent_tache_id');
        });
    }
};
