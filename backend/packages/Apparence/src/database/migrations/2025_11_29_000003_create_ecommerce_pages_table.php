<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // 'home', 'category', 'product'
            $table->string('slug')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['shop_id', 'type', 'slug']);
        });

        Schema::create('ecommerce_page_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecommerce_page_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title');
            $table->json('content_json')->nullable();
            $table->timestamps();

            $table->unique(['ecommerce_page_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_page_translations');
        Schema::dropIfExists('ecommerce_pages');
    }
};
