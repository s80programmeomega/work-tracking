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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Notre identifiant unique de transaction (UUID). Il sert AUSSI de clé
            // d'idempotence côté fournisseur (X-Reference-Id chez MTN) : si on renvoie
            // la même requête, le fournisseur ne débite pas deux fois.
            $table->uuid('reference')->unique();

            // Qui paie pour quoi.
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // initiateur

            // Quel fournisseur de paiement traite cette transaction.
            $table->string('provider'); // mtn_momo | orange_money

            // Montant figé au moment de l'achat (le prix du plan peut changer ensuite).
            $table->unsignedInteger('amount');       // plus petite unité, entier (XAF)
            $table->string('currency', 3)->default('XAF');

            // Cycle de vie du paiement. C'est ICI que se joue l'attente asynchrone :
            //   pending   : initié, en attente de confirmation du fournisseur
            //   succeeded : confirmé payé → l'abonnement est activé
            //   failed    : refusé / annulé
            //   expired   : l'utilisateur n'a pas validé à temps (ex. OTP non saisi)
            $table->string('status')->default('pending');

            // Références renvoyées par le fournisseur (jeton de paiement Orange,
            // financialTransactionId MTN, etc.) — utiles pour retrouver/vérifier.
            $table->string('provider_reference')->nullable();
            $table->string('provider_token')->nullable();

            // Trace brute de la dernière réponse/callback fournisseur, expurgée des secrets.
            // Précieux pour le débogage d'un paiement litigieux.
            $table->json('payload')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
