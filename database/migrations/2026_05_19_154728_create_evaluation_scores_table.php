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
        Schema::create('evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('periode_start');
            $table->date('periode_end');
            $table->string('critere', 100);
            $table->decimal('valeur', 6, 2);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'periode_start', 'periode_end']);
            $table->index(['user_id', 'critere']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_scores');
    }
};
