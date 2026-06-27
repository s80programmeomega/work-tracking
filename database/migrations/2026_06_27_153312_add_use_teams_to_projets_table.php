<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('projets', 'use_teams')) {
            Schema::table('projets', function (Blueprint $table) {
                $table->boolean('use_teams')->default(false)->after('archived_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('projets', 'use_teams')) {
            Schema::table('projets', function (Blueprint $table) {
                $table->dropColumn('use_teams');
            });
        }
    }
};
