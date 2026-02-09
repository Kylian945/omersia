<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 🔗 Lien vers user
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            // 🔗 Lien vers shipping method
            $table->foreignId('shipping_method_id')
                ->nullable()
                ->constrained('shipping_methods')
                ->nullOnDelete();

            $table->string('number')->unique()->nullable();             // ex: 1001
            $table->string('currency', 3)->default('EUR');

            $table->enum('status', [
                'draft', 'confirmed', 'processing', 'in_transit', 'out_for_delivery',
                'delivered', 'refunded', 'cancelled',
            ])->default('draft');

            $table->enum('payment_status', [
                'paid', 'unpaid', 'pending', 'refunded', 'partially_refunded',
            ])->default('pending');

            $table->enum('fulfillment_status', [
                'unfulfilled', 'partial', 'fulfilled', 'canceled',
            ])->default('unfulfilled');

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('shipping_total', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            $table->string('customer_email')->nullable();
            $table->string('customer_firstname')->nullable();
            $table->string('customer_lastname')->nullable();

            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();

            $table->datetime('placed_at')->nullable()->index();
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
