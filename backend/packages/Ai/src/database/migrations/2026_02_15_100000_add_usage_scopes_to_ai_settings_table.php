<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ai_settings', 'usage_scopes')) {
            Schema::table('ai_settings', function (Blueprint $table): void {
                $table->text('usage_scopes')->nullable()->after('scope');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ai_settings', 'usage_scopes')) {
            Schema::table('ai_settings', function (Blueprint $table): void {
                $table->dropColumn('usage_scopes');
            });
        }
    }
};
