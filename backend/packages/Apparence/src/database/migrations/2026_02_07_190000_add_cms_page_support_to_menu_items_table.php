<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('menu_items')) {
            return;
        }

        $hasCmsPageId = Schema::hasColumn('menu_items', 'cms_page_id');

        if (! $hasCmsPageId && Schema::hasTable('cms_pages')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->foreignId('cms_page_id')
                    ->nullable()
                    ->after('category_id')
                    ->constrained('cms_pages')
                    ->nullOnDelete();
            });
        }

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE `menu_items`
                MODIFY COLUMN `type` ENUM('category', 'cms_page', 'link', 'text') NOT NULL DEFAULT 'category'"
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('menu_items')) {
            return;
        }

        DB::table('menu_items')
            ->where('type', 'cms_page')
            ->update([
                'type' => 'link',
                'cms_page_id' => null,
            ]);

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE `menu_items`
                MODIFY COLUMN `type` ENUM('category', 'link', 'text') NOT NULL DEFAULT 'category'"
            );
        }

        $hasCmsPageId = Schema::hasColumn('menu_items', 'cms_page_id');

        if ($hasCmsPageId) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('cms_page_id');
            });
        }
    }
};
