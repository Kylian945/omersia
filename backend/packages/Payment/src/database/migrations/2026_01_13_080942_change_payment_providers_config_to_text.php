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
        Schema::table('payment_providers', function (Blueprint $table) {
            // Change config column from json to text to support encrypted data
            $table->text('config')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_providers', function (Blueprint $table) {
            // Revert back to json
            $table->json('config')->nullable()->change();
        });
    }
};
