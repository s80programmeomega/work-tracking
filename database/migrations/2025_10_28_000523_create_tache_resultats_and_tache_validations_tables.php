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
        // Create tache_resultats table for weekly evaluations
        Schema::create('tache_resultats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tache_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Résultats fields (from weekly evaluation sheet)
            $table->text('resultats_attendus')->nullable();
            $table->text('resultats_obtenus')->nullable();
            $table->integer('taux_realisation')->default(0); // 0-100
            $table->text('difficultes_rencontrees')->nullable();
            $table->text('solutions_envisagees')->nullable();
            $table->text('observations')->nullable();
            
            // Submission info
            $table->timestamp('soumis_le')->nullable();
            
            // N1 Validation (Direct supervisor)
            $table->boolean('valide_par_n1')->default(false);
            $table->foreignId('validateur_n1_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('valide_le_n1')->nullable();
            $table->text('commentaire_n1')->nullable();
            
            // N2 Validation (Supervisor's supervisor)
            $table->boolean('valide_par_n2')->default(false);
            $table->foreignId('validateur_n2_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('valide_le_n2')->nullable();
            $table->text('commentaire_n2')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('tache_id');
            $table->index('user_id');
            $table->index(['valide_par_n1', 'valide_par_n2']);
            $table->index('soumis_le');
        });

        // Create tache_validations table for task creation validation workflow
        Schema::create('tache_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tache_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            // Validators
            $table->foreignId('responsable_n1_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('responsable_n2_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Validation status
            $table->enum('status', ['pending', 'approved_n1', 'fully_approved', 'rejected'])->default('pending');
            $table->timestamp('validated_by_n1_at')->nullable();
            $table->timestamp('validated_by_n2_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();

            $table->index('tache_id');
            $table->index('status');
            $table->index(['responsable_n1_id', 'status']);
            $table->index(['responsable_n2_id', 'status']);
        });

        // Add visibility field to taches for public/private tasks
        Schema::table('taches', function (Blueprint $table) {
            $table->enum('visibility', ['public', 'private', 'members_only'])->default('members_only')->after('metadata');
            $table->index('visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });

        Schema::dropIfExists('tache_validations');
        Schema::dropIfExists('tache_resultats');
    }
};