<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('type'); // 'access', 'export', 'deletion', 'rectification'
            $table->string('status')->default('pending'); // pending, processing, completed, rejected

            // Informations sur la demande
            $table->text('reason')->nullable(); // Raison fournie par le client
            $table->text('admin_notes')->nullable(); // Notes internes pour les admins

            // Tracking du traitement
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('requested_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Pour les exports de données
            $table->string('export_file_path')->nullable(); // Chemin du fichier généré
            $table->timestamp('export_expires_at')->nullable(); // Expiration du lien (72h RGPD)

            // Pour les suppressions
            $table->boolean('data_deleted')->default(false);
            $table->json('deleted_data_summary')->nullable(); // Résumé des données supprimées

            $table->timestamps();

            // Index
            $table->index(['customer_id', 'type', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_requests');
    }
};
