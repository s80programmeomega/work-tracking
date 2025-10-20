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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship - attach to any model
            $table->string('documentable_type')->index();
            $table->unsignedBigInteger('documentable_id')->index();

            // File information
            $table->string('nom'); // Original filename
            $table->string('nom_stockage')->unique(); // Storage filename (unique hash)
            $table->string('extension', 10); // File extension
            $table->string('mime_type', 100); // MIME type
            $table->unsignedBigInteger('taille'); // File size in bytes
            $table->string('chemin'); // Storage path
            $table->string('disk')->default('local'); // Storage disk (local, s3, spaces)

            // Metadata
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional metadata (dimensions, duration, etc.)
            $table->string('hash_sha256')->nullable()->index(); // File hash for deduplication

            // Versioning
            $table->foreignId('parent_id')->nullable()->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_latest_version')->default(true);

            // Thumbnail for images
            $table->string('thumbnail_path')->nullable();

            // User tracking
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Statistics
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();

            // Visibility
            $table->enum('visibility', ['private', 'team', 'public'])->default('team');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['documentable_type', 'documentable_id']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
