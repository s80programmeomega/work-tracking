<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ✅ Ajouter colonnes pour double validation
        Schema::table('taches', function (Blueprint $table) {
            // Validation N1 (Responsable activité)
            $table->boolean('validation_n1_required')->default(true)->after('taux_realisation');
            $table->unsignedBigInteger('validated_n1_by')->nullable()->after('validation_n1_required');
            $table->timestamp('validated_n1_at')->nullable()->after('validated_n1_by');
            $table->text('commentaire_n1')->nullable()->after('validated_n1_at');

            // Validation N2 (Responsable projet/supérieur)
            $table->boolean('validation_n2_required')->default(true)->after('commentaire_n1');
            $table->unsignedBigInteger('validated_n2_by')->nullable()->after('validation_n2_required');
            $table->timestamp('validated_n2_at')->nullable()->after('validated_n2_by');
            $table->text('commentaire_n2')->nullable()->after('validated_n2_at');
            $table->integer('week_number')->nullable()->after('taux_realisation');
            $table->integer('year')->nullable()->after('week_number'); 

            // Index pour performances
            $table->index(['week_number', 'year']);
            $table->index(['validated_n1_at']);
            $table->index(['validated_n2_at']);
            $table->integer('estimated_hours')->nullable()->after('date_fin_reelle');

            // Foreign keys
            $table->foreign('validated_n1_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('validated_n2_by')->references('id')->on('users')->onDelete('set null');
        });

        // ✅ Améliorer table pivot tache_user
        Schema::table('tache_user', function (Blueprint $table) {
            // Ajouter permission de validation
            $table->boolean('can_validate')->default(false)->after('can_complete');
        });
    }

    public function down(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->dropForeign(['validated_n1_by']);
            $table->dropForeign(['validated_n2_by']);
            $table->dropIndex(['week_number', 'year']);
            $table->dropIndex(['validated_n1_at']);
            $table->dropIndex(['validated_n2_at']);

            $table->dropColumn([
                'validation_n1_required',
                'validated_n1_by',
                'validated_n1_at',
                'commentaire_n1',
                'validation_n2_required',
                'validated_n2_by',
                'validated_n2_at',
                'commentaire_n2',
                'week_number',
                'year',
            ]);
        });

        Schema::table('tache_user', function (Blueprint $table) {
            $table->dropColumn('can_validate');
        });
    }
};