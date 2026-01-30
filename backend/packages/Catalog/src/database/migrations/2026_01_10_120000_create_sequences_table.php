<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Sequence identifier (e.g., order_number, invoice_number_2026)');
            $table->string('prefix')->nullable()->comment('Optional prefix for generated values (e.g., ORD-, INV-2026-)');
            $table->unsignedBigInteger('current_value')->default(1000)->comment('Current sequence value');
            $table->unsignedInteger('padding')->default(8)->comment('Padding length for str_pad');
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};
