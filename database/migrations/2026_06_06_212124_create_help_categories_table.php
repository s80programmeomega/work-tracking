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
        Schema::create('help_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('nom_fr');
            $table->string('nom_en');
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();
            $table->string('icon', 32)->default('fa-book'); // classe FontAwesome
            $table->integer('position')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_categories');
    }
};
