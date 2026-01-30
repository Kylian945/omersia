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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();

            // Lien utilisateur
            $table->foreignId('customer_id')
                ->constrained()
                ->onDelete('cascade');

            // Surnom de l’adresse (maison, bureau, parents…)
            $table->string('label'); // "surnom"

            // Adresse postale
            $table->string('line1');          // Rue
            $table->string('line2')->nullable(); // Complément
            $table->string('postcode');
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country')->default('France');

            // Contact
            $table->string('phone')->nullable();

            // Par défaut
            $table->boolean('is_default_billing')->default(false);
            $table->boolean('is_default_shipping')->default(false);

            $table->timestamps();

            $table->index(['customer_id', 'is_default_billing']);
            $table->index(['customer_id', 'is_default_shipping']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
