<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Ajoute workspace_id si non présent
            if (!Schema::hasColumn('documents', 'workspace_id')) {
                $table->foreignId('workspace_id')
                    ->after('id')
                    ->constrained('workspaces')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'workspace_id')) {
                $table->dropConstrainedForeignId('workspace_id');
            }
        });
    }
    
};
