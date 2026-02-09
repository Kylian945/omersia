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
        // Ajouter le setting cart_type pour tous les thèmes actifs
        $themes = DB::table('themes')->where('is_active', true)->get();

        foreach ($themes as $theme) {
            // Vérifier si le setting n'existe pas déjà
            $exists = DB::table('theme_settings')
                ->where('theme_id', $theme->id)
                ->where('key', 'cart_type')
                ->exists();

            if (! $exists) {
                DB::table('theme_settings')->insert([
                    'theme_id' => $theme->id,
                    'key' => 'cart_type',
                    'value' => 'drawer',
                    'type' => 'select',
                    'group' => 'cart',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer le setting cart_type
        DB::table('theme_settings')
            ->where('key', 'cart_type')
            ->delete();
    }
};
