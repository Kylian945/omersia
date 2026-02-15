<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_default')->default(false);
            $table->text('config')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('scope')->unique()->default('global');
            $table->text('business_context')->nullable();
            $table->text('seo_objectives')->nullable();
            $table->text('forbidden_terms')->nullable();
            $table->string('writing_tone', 80)->default('professionnel');
            $table->string('content_locale', 10)->default('fr');
            $table->unsignedSmallInteger('title_max_length')->default(70);
            $table->unsignedSmallInteger('meta_description_max_length')->default(160);
            $table->text('additional_instructions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
        Schema::dropIfExists('ai_providers');
    }
};
