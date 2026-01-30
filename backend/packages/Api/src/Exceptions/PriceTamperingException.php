<?php

declare(strict_types=1);

namespace Omersia\Api\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PriceTamperingException extends Exception
{
    public function __construct(
        public readonly string $field,
        public readonly float $submitted,
        public readonly float $expected,
        ?string $message = null
    ) {
        parent::__construct($message ?? "Price tampering detected on {$field}: submitted {$submitted}, expected {$expected}");
    }

    public function render(): JsonResponse
    {
        // Log tampering attempt for security monitoring (server-side only)
        Log::channel('security')->alert('Price tampering attempt detected', [
            'field' => $this->field,
            'submitted' => $this->submitted,
            'expected' => $this->expected,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'url' => request()->fullUrl(),
        ]);

        // Return generic error message (no sensitive data to client)
        return response()->json([
            'error' => 'validation_error',
            'message' => 'Les prix soumis ne correspondent pas aux prix réels. Veuillez rafraîchir votre panier.',
        ], 422);
    }
}
