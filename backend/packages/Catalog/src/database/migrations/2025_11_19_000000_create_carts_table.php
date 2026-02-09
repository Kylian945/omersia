<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();

            // Token unique pour identifier le panier côté front
            $table->string('token')->unique();

            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('email')->nullable();

            $table->string('currency', 3)->default('EUR');

            $table->decimal('subtotal', 10, 2)->default(0); // total HT/TTC à toi de voir
            $table->unsignedInteger('total_qty')->default(0);

            $table->string('status')->default('open'); // open, ordered, abandoned, etc.
            $table->json('metadata')->nullable();

            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();

            $table->index('customer_id');
            $table->index('status');
            $table->index('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
