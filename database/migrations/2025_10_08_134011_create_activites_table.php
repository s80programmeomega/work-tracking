<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_id')->constrained()->onDelete('cascade');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('code')->unique(); // ACTIV-0001
            $table->foreignId('responsable_id')->constrained('users')->onDelete('restrict');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->integer('ordre')->default(0); // Display order
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->integer('progression')->default(0); // 0-100%
            $table->string('couleur', 7)->nullable(); // Hex color for UI
            $table->json('metadata')->nullable(); // For extensibility
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('projet_id');
            $table->index('responsable_id');
            $table->index('status');
            $table->index('ordre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activites');
    }
};
