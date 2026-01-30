<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Payments\PaymentProviderManager;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function __construct(
        protected PaymentProviderManager $providers
    ) {}

    public function handle(Request $request)
    {
        $driver = $this->providers->resolve('stripe');
        $driver->handleWebhook($request);

        return response()->json(['received' => true]);
    }
}
