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
        Schema::table('support_tickets', function (Blueprint $table) {
            // SLA : délai de réponse cible calculé à la création selon la catégorie
            $table->timestamp('sla_deadline')->nullable()->after('status');
            // Horodatages de cycle de vie
            $table->timestamp('first_responded_at')->nullable()->after('sla_deadline');
            $table->timestamp('resolved_at')->nullable()->after('first_responded_at');
            // Reproductibilité du problème
            $table->string('reproducibility')->nullable()->after('resolved_at'); // always|sometimes|rarely|not_reproducible|na
            $table->text('steps_to_reproduce')->nullable()->after('reproducibility');
            // Supprimer le champ mono-fichier au profit de la table support_ticket_attachments
            $table->dropColumn('attachment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['sla_deadline', 'first_responded_at', 'resolved_at', 'reproducibility', 'steps_to_reproduce']);
            $table->string('attachment')->nullable();
        });
    }
};
