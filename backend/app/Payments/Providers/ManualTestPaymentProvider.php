<?php

declare(strict_types=1);

namespace App\Payments\Providers;

use App\Events\Realtime\OrderUpdated;
use App\Payments\Contracts\PaymentProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Omersia\Catalog\Models\Order;
use Omersia\Payment\Models\Payment;
use Omersia\Payment\Models\PaymentProvider as PaymentProviderModel;

class ManualTestPaymentProvider implements PaymentProvider
{
    public function __construct(
        protected readonly PaymentProviderModel $config
    ) {}

    public function createPaymentIntent(Order $order, array $options = []): array
    {
        $amount = (int) round((float) $order->total * 100);
        $currency = strtoupper((string) ($order->currency ?? 'EUR'));
        $providerPaymentId = 'test_'.$order->id;

        $payment = Payment::query()->firstOrNew([
            'order_id' => $order->id,
            'payment_provider_id' => $this->config->id,
            'provider_code' => 'manual_test',
        ]);

        if (! is_string($payment->provider_payment_id) || trim($payment->provider_payment_id) === '') {
            $payment->provider_payment_id = $providerPaymentId;
        }

        $payment->status = 'succeeded';
        $payment->amount = $amount;
        $payment->currency = $currency;
        $payment->meta = array_merge($payment->meta ?? [], [
            'last_event' => 'manual_test.payment_succeeded',
            'paid_at' => now()->toIso8601String(),
        ]);
        $payment->save();

        $order->update([
            'payment_status' => 'paid',
            'payment_provider' => 'manual_test',
        ]);
        $order->refresh();
        event(OrderUpdated::fromModel($order));

        if ($order->isDraft()) {
            $order->confirm();
        }

        try {
            if (! $order->invoice) {
                app(\App\Services\InvoiceService::class)->generateInvoice($order);
            }
        } catch (\Throwable $e) {
            Log::warning('Manual test provider failed to generate invoice', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
        }

        Log::info('Manual test payment succeeded', [
            'order_id' => $order->id,
            'order_number' => $order->number,
            'payment_id' => $payment->id,
        ]);

        return [
            'provider' => 'manual_test',
            'status' => 'succeeded',
            'order_id' => $order->id,
            'order_number' => $order->number,
        ];
    }

    public function handleWebhook(Request $request): void
    {
        // No webhook for local manual test payments.
    }
}
