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
        Schema::create('document_permissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')->constrained()->cascadeOnDelete();

            // Permissionable (User or Role)
            $table->string('permissionable_type'); // User, Role
            $table->unsignedBigInteger('permissionable_id');

            // Permissions
            $table->boolean('can_view')->default(true);
            $table->boolean('can_download')->default(true);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_share')->default(false);

            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['permissionable_type', 'permissionable_id']);
            $table->index('document_id');
            $table->unique(['document_id', 'permissionable_type', 'permissionable_id'], 'doc_permission_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_permissions');
    }
};
