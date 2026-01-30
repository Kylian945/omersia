<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->foreignId('payment_provider_id')->constrained()->cascadeOnDelete();

            $table->string('provider_code');
            $table->string('provider_payment_id');
            $table->string('status')->default('pending');
            $table->integer('amount');
            $table->string('currency', 3)->default('EUR');

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
