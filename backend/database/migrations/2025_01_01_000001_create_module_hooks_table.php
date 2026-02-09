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
        Schema::create('module_hooks', function (Blueprint $table) {
            $table->id();
            $table->string('module_slug')->index();
            $table->string('hook_name')->index(); // checkout.shipping.after-methods, etc.
            $table->string('component_path'); // ColissimoRelaySelectorWrapper.tsx
            $table->text('condition')->nullable(); // shippingMethodCode === 'colissimo_point_relais'
            $table->integer('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Pour stocker des données supplémentaires
            $table->timestamps();

            // Un module peut avoir plusieurs hooks
            $table->unique(['module_slug', 'hook_name', 'component_path'], 'module_hook_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_hooks');
    }
};
