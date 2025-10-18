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
        // Create label_templates table
        Schema::create('label_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->enum('type_workflow', ['agile', 'kanban', 'waterfall', 'custom'])->default('custom');
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index('type_workflow');
            $table->index('is_default');
        });

        // Create label_template_items table
        Schema::create('label_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_template_id')
                ->constrained('label_templates')->onDelete('cascade');
            $table->string('nom');
            $table->string('couleur', 7); // Hex color code
            $table->text('description')->nullable();
            $table->integer('ordre')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('label_template_id');
            $table->index('ordre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('label_template_items');
        Schema::dropIfExists('label_templates');
    }
};
