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
        // Ajout de colonnes à shipping_methods
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->boolean('use_weight_based_pricing')->default(false)->after('is_active');
            $table->boolean('use_zone_based_pricing')->default(false)->after('use_weight_based_pricing');
            $table->decimal('free_shipping_threshold', 10, 2)->nullable()->after('use_zone_based_pricing');
            $table->text('description')->nullable()->after('name');
        });

        // Table des zones de livraison
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_method_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Ex: "France métropolitaine", "Europe", "DOM-TOM"
            $table->text('countries')->nullable(); // JSON des codes pays
            $table->text('postal_codes')->nullable(); // Pattern pour codes postaux
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Table des tarifs par poids/zone
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_method_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('min_weight', 10, 2)->nullable(); // En kg
            $table->decimal('max_weight', 10, 2)->nullable(); // En kg
            $table->decimal('price', 10, 2);
            $table->integer('priority')->default(0); // Pour ordre d'application des règles
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_zones');

        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropColumn([
                'use_weight_based_pricing',
                'use_zone_based_pricing',
                'free_shipping_threshold',
                'description',
            ]);
        });
    }
};
