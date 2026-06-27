<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('projets', 'visibility')) {
            Schema::table('projets', function (Blueprint $table) {
                $table->dropColumn('visibility');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('projets', 'visibility')) {
            Schema::table('projets', function (Blueprint $table) {
                $table->enum('visibility', ['public', 'team', 'private'])->default('team')->after('status');
            });
        }
    }
};
