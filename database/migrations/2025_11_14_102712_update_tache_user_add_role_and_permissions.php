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
        Schema::table('tache_user', function (Blueprint $table) {
            // Ajouter les colonnes pour le rôle et permissions
            $table->enum('role', ['assignee', 'validator', 'observer'])
                ->default('assignee')
                ->after('user_id');

            $table->boolean('can_edit')
                ->default(true)
                ->after('role');

            $table->boolean('can_complete')
                ->default(true)
                ->after('can_edit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tache_user', function (Blueprint $table) {
            $table->dropColumn(['role', 'can_edit', 'can_complete']);
        });
    }
};
