<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sous_tache_user', function (Blueprint $table) {
            $table->foreignId('sous_tache_id')->constrained('sous_taches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_complete')->default(true);
            $table->string('statut_individuel')->nullable();
            $table->unsignedTinyInteger('progression_individuelle')->default(0);
            $table->primary(['sous_tache_id', 'user_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sous_tache_user');
    }
};
