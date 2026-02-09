<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ex: "Menu principal"
            $table->string('slug')->unique(); // ex: "main", "footer"
            $table->string('location')->nullable(); // ex: "header", "footer"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');

            $table->foreignId('parent_id')->nullable()
                ->constrained('menu_items')
                ->onDelete('cascade'); // pour gérer des sous-menus plus tard

            $table->enum('type', ['category', 'link', 'text'])->default('category');

            $table->string('label');
            $table->unsignedBigInteger('category_id')->nullable(); // lié à ta table catégories
            $table->string('url')->nullable(); // pour type "link"

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(1);

            $table->timestamps();

            $table->index(['menu_id', 'position']);
        });

        // Si ta table catégories existe déjà :
        if (Schema::hasTable('categories')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->foreign('category_id')
                    ->references('id')->on('categories')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};
