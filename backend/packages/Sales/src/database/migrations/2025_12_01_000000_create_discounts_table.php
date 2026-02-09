<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id');

            $table->string('name');
            $table->enum('type', ['product', 'order', 'shipping', 'buy_x_get_y']);
            $table->enum('product_scope', ['all', 'products', 'collections'])->default('all');
            $table->enum('method', ['code', 'automatic']);

            $table->string('code')->nullable();

            $table->enum('value_type', ['percentage', 'fixed_amount', 'free_shipping'])->nullable();
            $table->decimal('value', 10, 2)->nullable();

            $table->decimal('min_subtotal', 10, 2)->nullable();
            $table->integer('min_quantity')->nullable();

            $table->integer('buy_quantity')->nullable();
            $table->integer('get_quantity')->nullable();
            $table->enum('buy_applies_to', ['collection', 'products', 'any'])->nullable();
            $table->enum('get_applies_to', ['collection', 'products', 'same_as_buy', 'any'])->nullable();
            $table->boolean('get_is_free')->default(true);
            $table->decimal('get_discount_value', 10, 2)->nullable();

            $table->enum('customer_selection', ['all', 'groups', 'customers'])->default('all');

            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->integer('usage_limit')->nullable();
            $table->integer('usage_limit_per_customer')->nullable();
            $table->boolean('applies_once_per_order')->default(false);

            $table->boolean('combines_with_product_discounts')->default(false);
            $table->boolean('combines_with_order_discounts')->default(false);
            $table->boolean('combines_with_shipping_discounts')->default(false);

            $table->integer('priority')->default(0);

            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
