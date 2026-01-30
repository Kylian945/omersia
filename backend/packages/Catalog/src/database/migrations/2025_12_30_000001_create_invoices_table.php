<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Lien vers la commande
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // Numéro de facture unique (ex: INV-2025-0001)
            $table->string('number')->unique();

            // Date d'émission de la facture
            $table->timestamp('issued_at');

            // Montant total de la facture
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('EUR');

            // Chemin du fichier PDF stocké
            $table->string('pdf_path')->nullable();

            // Données JSON pour la facture (snapshot de la commande au moment de la facturation)
            // Utile pour archivage même si la commande est modifiée
            $table->json('data')->nullable();

            $table->timestamps();

            // Index pour recherche rapide
            $table->index('number');
            $table->index('order_id');
            $table->index('issued_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
