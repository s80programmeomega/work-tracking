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
        Schema::table('tache_dependencies', function (Blueprint $table) {
            $table->enum('type', ['blocks', 'blocked_by', 'related'])->default('blocked_by')->after('depends_on_tache_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tache_dependencies', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
