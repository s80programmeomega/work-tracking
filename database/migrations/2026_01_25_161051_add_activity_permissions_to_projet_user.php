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
        Schema::table('projet_user', function (Blueprint $table) {
            $table->boolean('can_create_activity')->default(false)->after('can_delete_member');
            $table->boolean('can_edit_activity')->default(false)->after('can_create_activity');
            $table->boolean('can_delete_activity')->default(false)->after('can_edit_activity');
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projet_user', function (Blueprint $table) {
           $table->dropColumn([
                'can_create_activity',
                'can_edit_activity',
                'can_delete_activity',
            ]);
        });
    }
};
