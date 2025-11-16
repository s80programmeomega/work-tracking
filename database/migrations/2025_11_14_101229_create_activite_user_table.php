<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activite_user', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('activite_id');
            $table->unsignedBigInteger('user_id');

            $table->enum('role', ['responsable','collaborator','viewer'])
                  ->default('collaborator');

            // Permissions spécifiques aux activités
            $table->boolean('can_create_tasks')->default(false);
            $table->boolean('can_edit_tasks')->default(false);
            $table->boolean('can_delete_tasks')->default(false);
            $table->boolean('can_validate_results')->default(false);
            $table->boolean('can_assign_users')->default(false);

            $table->timestamps();

            $table->unique(['activite_id', 'user_id'], 'activite_user_unique');
            $table->index('role', 'idx_role');

            $table->foreign('activite_id')
                ->references('id')
                ->on('activites')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activite_user');
    }
};
