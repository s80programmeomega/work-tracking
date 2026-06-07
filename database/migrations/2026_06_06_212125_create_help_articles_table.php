<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('help_categories')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('titre_fr');
            $table->string('titre_en');
            $table->longText('body_fr');         // HTML Tiptap après sanitisation
            $table->longText('body_en');
            $table->longText('body_plain_fr');   // texte extrait, pour la recherche
            $table->longText('body_plain_en');
            $table->string('cover_image')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('published_at')->nullable(); // null = brouillon
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['category_id', 'published_at']);
        });

        // Index FULLTEXT en SQL brut (API fluent ne l'expose pas portablement).
        // Ignoré pour SQLite (tests) qui ne supporte pas FULLTEXT.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE help_articles
                ADD FULLTEXT idx_help_articles_search
                (titre_fr, titre_en, body_plain_fr, body_plain_en)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('help_articles');
    }
};
