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
        // Table pour les zones géographiques de taxation
        Schema::create('tax_zones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->string('name'); // ex: "France", "Union Européenne", "USA - Californie"
            $table->string('code')->unique(); // ex: "FR", "EU", "US-CA"
            $table->text('description')->nullable();
            $table->json('countries')->nullable(); // Liste des codes pays ISO (ex: ["FR", "BE", "DE"])
            $table->json('states')->nullable(); // Pour les états/provinces (ex: {"US": ["CA", "NY"]})
            $table->json('postal_codes')->nullable(); // Codes postaux spécifiques (ex: ["75*", "69*"])
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Ordre de priorité si plusieurs zones correspondent
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
        });

        // Table pour les taux de taxe
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tax_zone_id');
            $table->string('name'); // ex: "TVA Standard", "TVA Réduite", "Sales Tax"
            $table->string('type')->default('percentage'); // percentage or fixed
            $table->decimal('rate', 10, 4); // ex: 20.0000 pour 20%
            $table->boolean('compound')->default(false); // Taxe composée (calculée sur le prix + autres taxes)
            $table->boolean('shipping_taxable')->default(true); // Appliquer aux frais de port
            $table->integer('priority')->default(0); // Ordre d'application
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tax_zone_id')->references('id')->on('tax_zones')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('tax_zones');
    }
};
