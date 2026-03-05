<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->enum('status', ['draft', 'published', 'archived'])
                ->default('draft')
                ->after('type');
            $table->timestamp('published_at')->nullable()->after('status');
            $table->foreignId('published_by')
                ->nullable()
                ->after('published_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Preserve visibility of existing active pages during migration rollout.
        DB::table('cms_pages')
            ->where('is_active', true)
            ->update([
                'status' => 'published',
                'published_at' => DB::raw('COALESCE(updated_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn(['status', 'published_at']);
        });
    }
};
