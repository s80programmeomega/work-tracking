<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // S'assurer que created_by n'a pas de cascade — supprimer le créateur
        // ne doit pas effacer les grants qu'il a créés.
        // Sur certaines installations la FK n'existe pas du tout : on l'ajoute proprement.
        Schema::table('temporary_access', function (Blueprint $table) {
            $fks = collect(DB::select("
                SELECT kcu.CONSTRAINT_NAME
                FROM information_schema.REFERENTIAL_CONSTRAINTS rc
                JOIN information_schema.KEY_COLUMN_USAGE kcu
                    ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                    AND rc.CONSTRAINT_SCHEMA = kcu.TABLE_SCHEMA
                WHERE kcu.TABLE_NAME = 'temporary_access'
                AND kcu.COLUMN_NAME = 'created_by'
                AND kcu.TABLE_SCHEMA = DATABASE()
            "))->pluck('CONSTRAINT_NAME');

            if ($fks->isNotEmpty()) {
                $table->dropForeign(['created_by']);
            }

            // created_by doit être nullable pour que SET NULL fonctionne.
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('temporary_access', function (Blueprint $table) {
            $table->dropForeignIfExists('temporary_access_created_by_foreign');
        });
    }
};
