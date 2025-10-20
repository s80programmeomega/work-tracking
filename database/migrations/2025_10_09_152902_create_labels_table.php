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
        // Labels table
        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('couleur', 7); // Hex color code #RRGGBB
            $table->text('description')->nullable();
            $table->integer('ordre')->default(0);
            $table->timestamps();

            $table->index('ordre');
        });

        // Pivot table for task-label relationship
        Schema::create('label_tache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_id')->constrained()->onDelete('cascade');
            $table->foreignId('tache_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['label_id', 'tache_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('label_tache');
        Schema::dropIfExists('labels');
    }
};
