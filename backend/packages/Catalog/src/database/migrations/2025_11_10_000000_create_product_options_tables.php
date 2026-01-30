<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // ex: Taille, Couleur
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->string('value'); // ex: S, M, L, Rouge
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('sku')->unique()->nullable();
            $table->boolean('is_active')->default(true);

            $table->boolean('manage_stock')->default(true);
            $table->integer('stock_qty')->default(0);

            $table->decimal('price', 10, 2)->nullable();            // prix propre à la variante
            $table->decimal('compare_at_price', 10, 2)->nullable(); // prix barré

            // optionnel: titre lisible (ex: "Rouge / M")
            $table->string('name')->nullable();

            $table->timestamps();
        });

        Schema::create('product_variant_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_option_value_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['product_variant_id', 'product_option_value_id'],
                'product_variant_value_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_options');
    }
};
