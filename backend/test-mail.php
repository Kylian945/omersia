<?php

/**
 * Script de test pour vérifier l'envoi d'emails
 *
 * Usage: php test-mail.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "=== Test d'envoi d'email ===\n\n";

try {
    Mail::raw('Ceci est un email de test envoyé depuis Omersia.', function ($message) {
        $message->to('test@example.com')
            ->subject('Email de test Omersia');
    });

    echo "✅ Email envoyé avec succès !\n";
    echo "📧 Consultez l'email dans Mailpit : http://localhost:8025\n\n";

} catch (\Exception $e) {
    echo "❌ Erreur lors de l'envoi : ".$e->getMessage()."\n\n";
    echo "Vérifiez que :\n";
    echo "1. Mailpit est démarré : docker ps | grep mailpit\n";
    echo "2. Le fichier .env est configuré correctement\n";
    echo "3. MAIL_HOST=127.0.0.1 et MAIL_PORT=1025\n\n";
}
