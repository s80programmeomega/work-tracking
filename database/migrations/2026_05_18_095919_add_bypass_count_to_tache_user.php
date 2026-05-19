<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tache_user', function (Blueprint $table) {
            $table->unsignedInteger('bypass_count')->default(0)->after('escalades_abusives');
        });
    }

    public function down(): void
    {
        Schema::table('tache_user', function (Blueprint $table) {
            if (Schema::hasColumn('tache_user', 'bypass_count')) {
                $table->dropColumn('bypass_count');
            }
        });
    }
};
