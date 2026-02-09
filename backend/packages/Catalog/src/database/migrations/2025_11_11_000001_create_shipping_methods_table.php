<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();        // ex: colissimo_48h
            $table->string('name');                  // ex: Colissimo 48h
            $table->decimal('price', 10, 2)->default(0);
            $table->string('delivery_time')->nullable(); // ex: "2 à 3 jours ouvrés"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
