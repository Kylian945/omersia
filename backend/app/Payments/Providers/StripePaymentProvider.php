<?php

declare(strict_types=1);
// app/Payments/Providers/StripePaymentProvider.php

namespace App\Payments\Providers;

use App\Payments\Contracts\PaymentProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Omersia\Catalog\Models\Order;
use Omersia\Payment\Mail\PaymentConfirmationMail;
use Omersia\Payment\Mail\PaymentFailedMail;
use Omersia\Payment\Models\Payment;
use Omersia\Payment\Models\PaymentProvider as PaymentProviderModel;
use Stripe\StripeClient;

class StripePaymentProvider implements PaymentProvider
{
    protected StripeClient $client;

    protected PaymentProviderModel $config;

    public function __construct(PaymentProviderModel $config)
    {
        $this->config = $config;
        $secretKey = $config->config['secret_key'] ?? config('services.stripe.secret');

        $this->client = new StripeClient($secretKey);
    }

    public function createPaymentIntent(Order $order, array $options = []): array
    {
        $amount = (int) round($order->total * 100); // total en € → centimes
        $currency = $order->currency ?? 'eur';

        // 🔄 1) On regarde si un Payment existe déjà pour cette commande + ce provider
        /** @var Payment|null $existing */
        $existing = Payment::where('order_id', $order->id)
            ->where('payment_provider_id', $this->config->id)
            ->where('provider_code', 'stripe')
            ->first();

        if ($existing) {
            $intentId = $existing->provider_payment_id;

            // On met à jour l'intent côté Stripe avec le nouveau montant
            $intent = $this->client->paymentIntents->update($intentId, [
                'amount' => $amount,
                'currency' => $currency,
                // tu peux aussi repasser des metadata ici si tu veux
            ]);

            // On synchronise la ligne Payment
            $existing->amount = $amount;

            $meta = $existing->meta ?? [];
            $meta['intent'] = $intent->toArray();
            $existing->meta = $meta;
            $existing->save();

            return [
                'provider' => 'stripe',
                'client_secret' => $intent->client_secret,
            ];
        }

        // 🆕 2) Aucun Payment existant → on crée un nouvel intent
        $intent = $this->client->paymentIntents->create([
            'amount' => $amount,
            'currency' => $currency,
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_num' => (string) ($order->number ?? ''),
            ],
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        Payment::create([
            'order_id' => $order->id,
            'payment_provider_id' => $this->config->id,
            'provider_code' => 'stripe',
            'provider_payment_id' => $intent->id,
            'status' => 'pending',
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'meta' => ['intent' => $intent->toArray()],
        ]);

        return [
            'provider' => 'stripe',
            'client_secret' => $intent->client_secret,
        ];
    }

    public function handleWebhook(Request $request): void
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = $this->config->config['webhook_secret'] ?? config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\UnexpectedValueException|\Stripe\Exception\SignatureVerificationException $e) {
            // DCA-014: Logger les webhooks invalides (sécurité)
            Log::channel('security')->alert('Invalid Stripe webhook signature', [
                'message' => $e->getMessage(),
                'ip' => request()->ip(),
                'has_signature' => ! empty($sigHeader),
            ]);
            abort(400, 'Invalid webhook');
        }

        // DCA-014: Logger la réception du webhook
        Log::channel('transactions')->info('Stripe webhook received', [
            'event_id' => $event->id,
            'event_type' => $event->type,
            'ip' => request()->ip(),
        ]);

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $intent = $event->data->object;
                $this->markPaymentAsSucceeded($intent);
                break;
            case 'payment_intent.payment_failed':
                $intent = $event->data->object;
                $this->markPaymentAsFailed($intent);
                break;
        }
    }

    protected function markPaymentAsSucceeded($intent): void
    {
        /** @var Payment|null $payment */
        $payment = Payment::where('provider_code', 'stripe')
            ->where('provider_payment_id', $intent->id)
            ->first();

        if (! $payment) {
            return;
        }

        $payment->update([
            'status' => 'succeeded',
            'meta' => array_merge($payment->meta ?? [], [
                'last_event' => 'payment_intent.succeeded',
            ]),
        ]);

        $order = $payment->order;

        // DCA-014: Logger le succès du paiement
        Log::channel('transactions')->info('Payment succeeded', [
            'payment_id' => $payment->id,
            'payment_provider_id' => $payment->provider_payment_id,
            'order_id' => $order->id,
            'order_number' => $order->number,
            'customer_id' => $order->customer_id,
            'customer_email' => $order->customer_email,
            'amount' => $payment->amount / 100, // Convertir centimes en euros
            'currency' => $payment->currency,
            'provider' => 'stripe',
            'stripe_intent_id' => $intent->id,
            'payment_method' => $intent->payment_method ?? null,
        ]);

        // Mettre à jour le statut de paiement
        $order->update([
            'payment_status' => 'paid',
            'payment_provider' => 'stripe',
        ]);

        // 🔥 Confirmer la commande si elle est en brouillon
        if ($order->isDraft()) {
            $order->confirm();
            Log::info('Order confirmed after successful payment', [
                'order_id' => $order->id,
                'order_number' => $order->number,
            ]);
        }

        // 📄 Générer la facture
        try {
            $invoiceService = app(\App\Services\InvoiceService::class);
            $invoice = $invoiceService->generateInvoice($order);

            if ($invoice) {
                // DCA-014: Logger la génération de facture
                Log::channel('transactions')->info('Invoice generated', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->number,
                    'order_id' => $order->id,
                    'order_number' => $order->number,
                    'customer_id' => $order->customer_id,
                    'customer_email' => $order->customer_email,
                    'total' => $order->total,
                    'currency' => $order->currency,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erreur génération facture: '.$e->getMessage(), [
                'order_id' => $order->id,
            ]);
        }

        // Envoi de l'email de confirmation de paiement
        try {
            $customer = $order->customer;
            if ($customer) {
                Mail::to($customer->email)->send(new PaymentConfirmationMail($order, $payment));
            }
        } catch (\Exception $e) {
            Log::error('Erreur envoi email confirmation de paiement: '.$e->getMessage(), [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
            ]);
        }
    }

    protected function markPaymentAsFailed($intent): void
    {
        $payment = Payment::where('provider_code', 'stripe')
            ->where('provider_payment_id', $intent->id)
            ->first();

        if (! $payment) {
            return;
        }

        $failureReason = $intent->last_payment_error->message ?? 'Paiement refusé';
        $failureCode = $intent->last_payment_error->code ?? null;

        $payment->update([
            'status' => 'failed',
            'meta' => array_merge($payment->meta ?? [], [
                'last_event' => 'payment_intent.payment_failed',
            ]),
        ]);

        $order = $payment->order;

        // DCA-014: Logger l'échec du paiement
        Log::channel('transactions')->warning('Payment failed', [
            'payment_id' => $payment->id,
            'payment_provider_id' => $payment->provider_payment_id,
            'order_id' => $order->id,
            'order_number' => $order->number,
            'customer_id' => $order->customer_id,
            'customer_email' => $order->customer_email,
            'amount' => $payment->amount / 100, // Convertir centimes en euros
            'currency' => $payment->currency,
            'provider' => 'stripe',
            'stripe_intent_id' => $intent->id,
            'failure_reason' => $failureReason,
            'failure_code' => $failureCode,
        ]);

        $order->update([
            'payment_status' => 'failed',
        ]);

        // Envoi de l'email d'échec de paiement
        try {
            $customer = $order->customer;
            if ($customer) {
                $frontUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
                $retryUrl = $frontUrl.'/checkout/'.$order->id;

                Mail::to($customer->email)->send(new PaymentFailedMail($order, $failureReason, $retryUrl));
            }
        } catch (\Exception $e) {
            Log::error('Erreur envoi email échec de paiement: '.$e->getMessage(), [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
            ]);
        }
    }
}
