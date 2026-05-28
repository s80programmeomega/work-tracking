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
        Schema::table('projet_invitations', function (Blueprint $table) {
            $table->string('role', 50)->default('collaborateur')->change();
        });
    }

    public function down(): void
    {
        Schema::table('projet_invitations', function (Blueprint $table) {
            $table->enum('role', ['admin', 'member', 'viewer', 'manager', 'cadre', 'collaborateur', 'stagiaire', 'observateur'])->default('collaborateur')->change();
        });
    }
};
