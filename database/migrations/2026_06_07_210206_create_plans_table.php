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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();          // free, starter, pro, …
            $table->string('nom_fr');
            $table->string('nom_en');
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();
            $table->boolean('is_free')->default(false); // le plan de repli gratuit
            $table->unsignedInteger('price')->default(0); // prix en plus petite unité (XAF entier)
            $table->string('currency', 3)->default('XAF');
            $table->string('billing_period')->default('monthly'); // monthly | yearly
            // Limites du plan (-1 = illimité). SubscriptionService les lit en priorité sur la config.
            $table->integer('max_members')->default(-1);
            $table->integer('max_storage_mb')->default(-1);
            $table->integer('max_file_size_mb')->default(-1);
            $table->json('features')->nullable();        // liste de fonctionnalités affichées
            $table->boolean('is_active')->default(true);  // visible/souscriptible
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
