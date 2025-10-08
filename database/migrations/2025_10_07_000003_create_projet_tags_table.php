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
        Schema::create('projet_tags', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->string('couleur')->default('#6B7280');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('projet_projet_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_id')->constrained('projets')->onDelete('cascade');
            $table->foreignId('projet_tag_id')->constrained('projet_tags')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['projet_id', 'projet_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projet_projet_tag');
        Schema::dropIfExists('projet_tags');
    }
};
