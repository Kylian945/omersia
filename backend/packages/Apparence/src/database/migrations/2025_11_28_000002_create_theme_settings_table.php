<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->string('key'); // ex: "colors.primary", "typography.heading_font"
            $table->text('value')->nullable(); // stocké en JSON ou texte
            $table->string('type')->default('text'); // text, color, number, json, boolean, select
            $table->string('group')->nullable(); // colors, typography, layout, etc.
            $table->timestamps();

            $table->unique(['theme_id', 'key']);
            $table->index(['theme_id', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
    }
};
