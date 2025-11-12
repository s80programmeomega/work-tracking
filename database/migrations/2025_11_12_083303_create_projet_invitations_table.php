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
        Schema::create('projet_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_id')->constrained()->onDelete('cascade');
            $table->string('email');
            $table->enum('role', ['admin', 'member', 'viewer'])->default('member');
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_invite')->default(false);
            $table->string('token', 64)->unique();
            $table->foreignId('invited_by')->constrained('users');
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'cancelled', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['projet_id', 'status']);
            $table->index(['email', 'status']);
            $table->index('token');
        });

        // Ajouter created_by aux tables principales
        Schema::table('projets', function (Blueprint $table) {
            if (!Schema::hasColumn('projets', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('responsable_id')->constrained('users');
            }
        });

        Schema::table('activites', function (Blueprint $table) {
            if (!Schema::hasColumn('activites', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('responsable_id')->constrained('users');
            }
        });

        Schema::table('taches', function (Blueprint $table) {
            if (!Schema::hasColumn('taches', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('validateur_id')->constrained('users');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projet_invitations');

        Schema::table('projets', function (Blueprint $table) {
            if (Schema::hasColumn('projets', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
        });

        Schema::table('activites', function (Blueprint $table) {
            if (Schema::hasColumn('activites', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
        });

        Schema::table('taches', function (Blueprint $table) {
            if (Schema::hasColumn('taches', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
        });
    }
};