<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_members', function (Blueprint $table) {
            // Marque les lignes créées automatiquement lors d'un accès temporaire.
            // Ces lignes sont supprimées à l'expiration ou à la révocation du compte.
            $table->boolean('is_temp_access')->default(false)->after('banned_by');
        });
    }

    public function down(): void
    {
        Schema::table('workspace_members', function (Blueprint $table) {
            $table->dropColumn('is_temp_access');
        });
    }
};
