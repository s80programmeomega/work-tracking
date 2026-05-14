<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activite_user', function (Blueprint $table) {
            $table->boolean('can_delete_member')->default(false)->after('can_assign_users');
        });
    }

    public function down(): void
    {
        Schema::table('activite_user', function (Blueprint $table) {
            $table->dropColumn('can_delete_member');
        });
    }
};
