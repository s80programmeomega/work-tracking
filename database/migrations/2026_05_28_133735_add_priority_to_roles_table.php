<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedTinyInteger('priority')->default(99)->after('guard_name');
        });

        $priorities = [
            'super_admin' => 1,
            'directeur' => 2,
            'owner' => 3,
            'manager' => 4,
            'cadre' => 5,
            'task_responsable' => 5,
            'collaborateur' => 6,
            'stagiaire' => 6,
            'observateur' => 7,
            'utilisateur' => 8,
        ];

        foreach ($priorities as $name => $priority) {
            DB::table('roles')->where('name', $name)->update(['priority' => $priority]);
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};
