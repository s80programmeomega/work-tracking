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
        // Create workspaces table
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('code')->unique();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('logo')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('owner_id');
            $table->index('is_active');
            $table->index('created_at');
        });

        // Create workspace_members pivot table
        Schema::create('workspace_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['owner', 'admin', 'member', 'viewer'])->default('member');
            $table->json('permissions')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['workspace_id', 'user_id']);
            $table->index('workspace_id');
            $table->index('user_id');
            $table->index('role');
        });

        // Add workspace_id to projets table
        Schema::table('projets', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index('workspace_id');
        });

        // Add parent_tache_id for sub-tasks
        Schema::table('taches', function (Blueprint $table) {
            $table->foreignId('parent_tache_id')->nullable()->after('activite_id')->constrained('taches')->onDelete('cascade');
            $table->index('parent_tache_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->dropForeign(['parent_tache_id']);
            $table->dropColumn('parent_tache_id');
        });

        Schema::table('projets', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropColumn('workspace_id');
        });

        Schema::dropIfExists('workspace_members');
        Schema::dropIfExists('workspaces');
    }
};