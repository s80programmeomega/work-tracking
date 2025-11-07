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
        Schema::create('taches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activite_id')->constrained('activites')->onDelete('cascade');
            $table->string('titre');
            $table->text('description')->nullable();
            $table->text('objectif')->nullable();
            $table->text('indicateurs_resultats')->nullable();
            $table->enum('statut', ['a_faire', 'en_cours', 'termine'])->default('a_faire');
            $table->enum('priorite', ['faible', 'moyenne', 'elevee', 'critique'])->default('moyenne');
            $table->date('echeance')->nullable();
            $table->date('date_fin_reelle')->nullable();
            $table->integer('taux_realisation')->default(0); // 0-100%
            $table->boolean('validation_superieur')->default(false);
            $table->boolean('verrou_reevaluation')->default(false);
            $table->text('commentaire')->nullable();
            $table->foreignId('validateur_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('ordre')->default(0);
            $table->string('couleur')->nullable();
            $table->string('image_couverture')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Unique constraint: titre must be unique per activite
            $table->unique(['activite_id', 'titre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taches');
    }
};
