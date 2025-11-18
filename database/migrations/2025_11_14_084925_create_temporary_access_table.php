<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_access', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->string('accessible_type');     // Projet, Activite, etc.
            $table->unsignedBigInteger('accessible_id');
            $table->string('role', 50);
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('created_by');

            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')
                ->default(0)
                ->after('email');

            $table->index('is_super_admin', 'idx_super_admin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_access');
         Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_super_admin');
            $table->dropColumn('is_super_admin');
        });
    }
};
