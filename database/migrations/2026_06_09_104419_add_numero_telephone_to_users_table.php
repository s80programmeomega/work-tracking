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
        Schema::table('users', function (Blueprint $table) {
            // Le numéro de téléphone était référencé partout (fillable, FormRequest,
            // UserResource, formulaire profil) mais la colonne n'avait jamais été
            // migrée → erreur SQL 1054 à la mise à jour du profil. On l'ajoute.
            if (! Schema::hasColumn('users', 'numero_telephone')) {
                $table->string('numero_telephone', 20)->nullable()->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'numero_telephone')) {
                $table->dropColumn('numero_telephone');
            }
        });
    }
};
