<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sous_taches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tache_id')->constrained('taches')->cascadeOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->enum('statut', ['a_faire', 'en_cours', 'en_retard', 'termine', 'a_refaire', 'annule'])->default('a_faire');
            $table->unsignedTinyInteger('progression')->default(0);
            $table->unsignedTinyInteger('poids')->default(0);
            $table->date('date_echeance')->nullable();
            $table->boolean('validation_n0_required')->default(false);
            $table->boolean('validation_n1_required')->default(false);
            $table->boolean('validation_n2_required')->default(false);
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sous_taches');
    }
};
