<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_deletion_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id'); // Ne pas contraindre car customer sera supprimé
            $table->string('customer_email');
            $table->foreignId('data_request_id')->nullable()->constrained('data_requests')->onDelete('set null');

            // Données supprimées
            $table->json('deleted_tables'); // Liste des tables où des données ont été supprimées
            $table->json('anonymized_tables')->nullable(); // Tables où les données ont été anonymisées
            $table->integer('total_records_deleted');
            $table->integer('total_records_anonymized')->default(0);

            // Traçabilité légale
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('deleted_at');
            $table->string('deletion_method'); // 'full_deletion', 'anonymization', 'partial'
            $table->text('notes')->nullable();

            $table->timestamps();

            // Index pour audit
            $table->index(['customer_email', 'deleted_at']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_deletion_logs');
    }
};
