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
        Schema::create('projets', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->date('date_debut')->default(now()->toDateString());
            $table->date('date_fin');
            $table->foreignId('responsable_id')->constrained('users')->onDelete('cascade');
            $table->decimal('budget', 15, 2)->nullable();
            $table->enum('status', ['planifie', 'en_cours', 'suspendu', 'termine', 'annule'])->default('planifie');
            $table->enum('priorite', ['basse', 'normale', 'haute', 'critique'])->default('normale');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projets');
    }
};
