<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('default_locale')->default('fr');
            $table->unsignedBigInteger('default_currency_id')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('display_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('shop_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('domain')->unique();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('symbol')->default('€');
            $table->decimal('rate', 12, 6)->default(1);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('locales', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // fr, en
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locales');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('shop_domains');
        Schema::dropIfExists('shops');
    }
};
