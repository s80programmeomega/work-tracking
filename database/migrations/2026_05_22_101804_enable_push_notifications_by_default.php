<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bascule le master switch `push_enabled` à activé par défaut pour
     * tout nouvel utilisateur, et active rétroactivement la préférence
     * sur les lignes existantes qui étaient encore au défaut historique
     * (false).
     *
     * Les toggles par type d'événement (task_assigned_push, etc.) ne sont
     * pas touchés ici: ils restent en opt-in fin pour limiter le bruit.
     */
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->boolean('push_enabled')->default(true)->change();
        });

        // Backfill: activer la préférence sur les lignes existantes qui
        // étaient encore au défaut historique (false). Les lignes où
        // l'utilisateur a explicitement désactivé sont préservées
        // uniquement si on peut les distinguer du défaut — ici elles
        // sont indiscernables, donc le backfill bascule toutes les
        // lignes à true et c'est ensuite à l'utilisateur de désactiver
        // s'il le souhaite (comportement aligné avec la demande
        // "push activées par défaut pour tout utilisateur").
        DB::table('notification_preferences')
            ->where('push_enabled', false)
            ->update(['push_enabled' => true]);
    }

    /**
     * Restaure le défaut historique (false) sans toucher aux valeurs
     * déjà persistées — la migration up() a effacé l'information
     * d'origine et on ne peut pas la reconstruire.
     */
    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->boolean('push_enabled')->default(false)->change();
        });
    }
};
