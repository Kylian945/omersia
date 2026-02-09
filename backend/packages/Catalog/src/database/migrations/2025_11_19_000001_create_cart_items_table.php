<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cart_id');

            // Mapping avec ton CartItem TS
            $table->unsignedBigInteger('product_id'); // CartItem.id
            $table->unsignedBigInteger('variant_id')->nullable(); // CartItem.variantId

            $table->string('name');
            $table->string('variant_label')->nullable();

            $table->decimal('unit_price', 10, 2); // CartItem.price
            $table->decimal('old_price', 10, 2)->nullable(); // CartItem.oldPrice

            $table->unsignedInteger('qty');

            $table->string('image_url')->nullable();

            $table->json('options')->nullable(); // si tu veux des trucs en plus plus tard

            $table->timestamps();

            $table->foreign('cart_id')
                ->references('id')
                ->on('carts')
                ->onDelete('cascade');

            $table->index(['cart_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
