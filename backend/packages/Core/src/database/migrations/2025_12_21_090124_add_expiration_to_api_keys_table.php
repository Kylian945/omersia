<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('key');
            $table->timestamp('last_used_at')->nullable()->after('expires_at');
            $table->string('last_used_ip', 45)->nullable()->after('last_used_at');
            $table->unsignedBigInteger('usage_count')->default(0)->after('last_used_ip');
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'last_used_at', 'last_used_ip', 'usage_count']);
        });
    }
};
