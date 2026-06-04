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
        Schema::table('documents', function (Blueprint $table) {
            // Texte extrait du fichier physique (PDF, docx, xlsx, txt…).
            // Rempli de façon asynchrone par ExtractDocumentTextJob après l'upload.
            // Limité à 50 000 chars en base ; Typesense n'indexe que les 5 000 premiers.
            $table->longText('content_text')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'content_text')) {
                $table->dropColumn('content_text');
            }
        });
    }
};
