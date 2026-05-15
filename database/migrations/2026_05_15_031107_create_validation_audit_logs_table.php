<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validation_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tache_resultat_id')->constrained('tache_resultats')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action'); // approuve, renvoye, timeout, bypass, n1_valide, n1_rejete, n2_valide, n2_rejete
            $table->json('context')->nullable(); // taux_realisation, commentaire, motif, delay_hours, etc.
            $table->timestamp('created_at'); // immutable — no updated_at (R6)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_audit_logs');
    }
};
