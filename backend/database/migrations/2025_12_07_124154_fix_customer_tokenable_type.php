<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('personal_access_tokens')
            ->where('tokenable_type', 'Omersia\Admin\Models\Customer')
            ->update(['tokenable_type' => 'Omersia\Customer\Models\Customer']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('personal_access_tokens')
            ->where('tokenable_type', 'Omersia\Customer\Models\Customer')
            ->update(['tokenable_type' => 'Omersia\Admin\Models\Customer']);
    }
};
