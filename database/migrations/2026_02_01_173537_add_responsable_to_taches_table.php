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
        Schema::table('taches', function (Blueprint $table) {
            $table->foreignId('responsable_id')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();

                  // ✅ Ajouter un index pour les requêtes
            $table->index('responsable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->dropForeign(['responsable_id']);
            $table->dropIndex(['responsable_id']);
            $table->dropColumn('responsable_id');
        });
    }
};