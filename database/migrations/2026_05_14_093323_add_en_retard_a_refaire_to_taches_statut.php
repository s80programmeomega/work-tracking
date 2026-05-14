<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend the taches.statut MySQL ENUM to include the two new values
        DB::statement("ALTER TABLE taches MODIFY COLUMN statut ENUM('a_faire','en_cours','en_attente','termine','annule','en_retard','a_refaire') NOT NULL DEFAULT 'a_faire'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE taches MODIFY COLUMN statut ENUM('a_faire','en_cours','en_attente','termine','annule') NOT NULL DEFAULT 'a_faire'");
    }
};
