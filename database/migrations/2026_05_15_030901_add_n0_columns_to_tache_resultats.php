<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add statut column with N0 circuit values
        DB::statement("
            ALTER TABLE tache_resultats
            ADD COLUMN statut ENUM(
                'brouillon',
                'en_verification_n0',
                'en_validation_n1',
                'en_validation_n2',
                'valide',
                'rejete',
                'a_refaire'
            ) NOT NULL DEFAULT 'brouillon' AFTER id
        ");

        Schema::table('tache_resultats', function (Blueprint $table) {
            $table->timestamp('soumis_n0_le')->nullable()->after('soumis_le');
            $table->enum('action_n0', ['approuve', 'renvoye', 'timeout'])->nullable()->after('soumis_n0_le');
            $table->text('commentaire_n0')->nullable()->after('action_n0');
            $table->foreignId('n0_actor_id')->nullable()->after('commentaire_n0')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('action_n0_le')->nullable()->after('n0_actor_id');
        });
    }

    public function down(): void
    {
        Schema::table('tache_resultats', function (Blueprint $table) {
            $table->dropForeign(['n0_actor_id']);
            $table->dropColumn(['soumis_n0_le', 'action_n0', 'commentaire_n0', 'n0_actor_id', 'action_n0_le']);
        });

        DB::statement('ALTER TABLE tache_resultats DROP COLUMN statut');
    }
};
