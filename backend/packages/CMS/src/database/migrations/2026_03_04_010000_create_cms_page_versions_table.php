<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_page_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_translation_id')
                ->constrained('cms_page_translations')
                ->cascadeOnDelete();
            $table->json('content_json')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('label')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['page_translation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_page_versions');
    }
};

