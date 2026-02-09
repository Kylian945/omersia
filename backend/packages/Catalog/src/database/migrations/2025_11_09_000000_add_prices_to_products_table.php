<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Prix de base TTC (ou HT selon ton choix, mais fixe-le clairement)
            $table->decimal('price', 10, 2)->default(0)->after('stock_qty');

            // Prix barré / avant remise (optionnel)
            $table->decimal('compare_at_price', 10, 2)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['price', 'compare_at_price']);
        });
    }
};
