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
        Schema::table('workspaces', function (Blueprint $table) {
            // Plan souscrit (null = aucun plan explicite → repli sur le plan gratuit).
            $table->foreignId('plan_id')->nullable()->after('subscription_mode')
                ->constrained('plans')->nullOnDelete();

            // Cycle de vie de l'abonnement (pilote le middleware global) :
            //   trial   : période d'essai en cours
            //   active  : abonnement payant actif
            //   lapsed  : essai/abonnement expiré → rétrogradé au plan gratuit (accès limité)
            //   locked  : accès révoqué (verrou dur → HTTP 402)
            $table->string('subscription_status')->default('trial')->after('plan_id');

            // Fin de la période payante en cours (null en essai/gratuit).
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            if (Schema::hasColumn('workspaces', 'plan_id')) {
                $table->dropConstrainedForeignId('plan_id');
            }
            $table->dropColumn(['subscription_status', 'subscription_ends_at']);
        });
    }
};
