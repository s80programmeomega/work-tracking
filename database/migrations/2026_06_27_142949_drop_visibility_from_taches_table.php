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
        if (Schema::hasColumn('taches', 'visibility')) {
            Schema::table('taches', function (Blueprint $table) {
                $table->dropColumn('visibility');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('taches', 'visibility')) {
            Schema::table('taches', function (Blueprint $table) {
                $table->enum('visibility', ['public', 'private', 'members_only'])->default('members_only')->after('commentaire');
            });
        }
    }
};
