<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

class SanitizedFormatter extends LineFormatter
{
    private array $sensitiveKeys = [
        'password',
        'secret',
        'token',
        'api_key',
        'authorization',
        'credit_card',
        'cvv',
        'stripe',
    ];

    public function format(LogRecord $record): string
    {
        $context = $record->context;
        $context = $this->sanitize($context);

        $record = $record->with(context: $context);

        return parent::format($record);
    }

    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);

            foreach ($this->sensitiveKeys as $sensitive) {
                if (str_contains($lowerKey, $sensitive)) {
                    $data[$key] = '[REDACTED]';
                    break;
                }
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }
}
