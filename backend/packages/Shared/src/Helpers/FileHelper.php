<?php

declare(strict_types=1);

namespace Omersia\Shared\Helpers;

use Illuminate\Support\Str;

class FileHelper
{
    public static function sanitizeFilename(string $filename): string
    {
        // Récupérer l'extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Remplacer les points par des tirets avant slugification
        $name = str_replace('.', '-', $name);

        // Slugifier le nom
        $safeName = Str::slug($name, '-');

        // Limiter la longueur
        $safeName = Str::limit($safeName, 100, '');

        // Ajouter un identifiant unique
        $safeName = $safeName.'-'.Str::random(8);

        // Extensions autorisées
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf'];
        $extension = strtolower($extension);

        if (! in_array($extension, $allowedExtensions)) {
            $extension = 'bin';
        }

        return $safeName.'.'.$extension;
    }
}
