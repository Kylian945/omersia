<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cookie_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->string('session_id')->nullable()->index(); // Pour les visiteurs non connectés
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            // Consentements par catégorie
            $table->boolean('necessary')->default(true); // Toujours true
            $table->boolean('functional')->default(false);
            $table->boolean('analytics')->default(false);
            $table->boolean('marketing')->default(false);

            // Métadonnées
            $table->string('consent_version')->default('1.0'); // Version de la politique
            $table->timestamp('consented_at');
            $table->timestamp('expires_at')->nullable(); // Expiration du consentement (13 mois RGPD)

            $table->timestamps();

            // Index pour retrouver rapidement les consentements
            $table->index(['customer_id', 'created_at']);
            $table->index(['session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cookie_consents');
    }
};
