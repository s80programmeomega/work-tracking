<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projet_invitations', function (Blueprint $table) {
            $table->boolean('can_delete_member')->default(false)->after('can_invite');
            $table->boolean('can_create_activity')->default(false)->after('can_delete_member');
            $table->boolean('can_edit_activity')->default(false)->after('can_create_activity');
            $table->boolean('can_delete_activity')->default(false)->after('can_edit_activity');
        });
    }

    public function down(): void
    {
        Schema::table('projet_invitations', function (Blueprint $table) {
            if (Schema::hasColumn('projet_invitations', 'can_delete_member')) {
                $table->dropColumn('can_delete_member');
            }
            if (Schema::hasColumn('projet_invitations', 'can_create_activity')) {
                $table->dropColumn('can_create_activity');
            }
            if (Schema::hasColumn('projet_invitations', 'can_edit_activity')) {
                $table->dropColumn('can_edit_activity');
            }
            if (Schema::hasColumn('projet_invitations', 'can_delete_activity')) {
                $table->dropColumn('can_delete_activity');
            }
        });
    }
};
