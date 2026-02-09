<?php

declare(strict_types=1);

namespace Omersia\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use Omersia\Payment\Http\Requests\UpdateStripeConfigRequest;
use Omersia\Payment\Models\PaymentProvider;

class PaymentController extends Controller
{
    /**
     * Page principale "Paramètres > Paiements"
     */
    public function index()
    {
        PaymentProvider::ensureCoreProviders();

        // On récupère le provider Stripe
        $stripeProvider = PaymentProvider::query()
            ->where('code', 'stripe')
            ->firstOrFail();

        $stripeConfig = $stripeProvider->config ?? [];

        $stripeMode = $stripeConfig['mode'] ?? 'test';
        $stripeEnabled = (bool) $stripeProvider->enabled;
        $primaryCurrency = config('app.currency', 'EUR');

        // Autres moyens de paiement (modules, etc.)
        $paymentProviders = PaymentProvider::query()
            ->where('code', '!=', 'stripe')
            ->orderBy('name')
            ->get();

        return view('admin::settings.payments.index', [
            'stripeMode' => $stripeMode,
            'stripeEnabled' => $stripeEnabled,
            'primaryCurrency' => $primaryCurrency,
            'paymentProviders' => $paymentProviders,
            'modulesCount' => $paymentProviders->count(),
        ]);
    }

    /**
     * Formulaire de configuration Stripe
     */
    public function stripe()
    {
        PaymentProvider::ensureCoreProviders();

        $stripeProvider = PaymentProvider::query()
            ->where('code', 'stripe')
            ->firstOrFail();

        $config = $stripeProvider->config ?? [];

        $data = [
            'enabled' => (bool) $stripeProvider->enabled,
            'mode' => $config['mode'] ?? 'test', // test|live
            'currency' => strtoupper($config['currency'] ?? 'EUR'),
            'publishable_key' => $config['publishable_key'] ?? null,
            'secret_key' => $config['secret_key'] ?? null,
            'webhook_secret' => $config['webhook_secret'] ?? null,
        ];

        return view('admin::settings.payments.stripe', $data);
    }

    /**
     * Sauvegarde de la configuration Stripe
     */
    public function updateStripe(UpdateStripeConfigRequest $request)
    {
        PaymentProvider::ensureCoreProviders();
        $validated = $request->validated();

        $stripeProvider = PaymentProvider::query()
            ->where('code', 'stripe')
            ->firstOrFail();

        $enabled = $request->boolean('enabled');
        $config = $stripeProvider->config ?? [];

        $config['mode'] = $validated['mode'];
        $config['currency'] = strtolower($validated['currency'] ?? ($config['currency'] ?? 'eur'));
        $config['publishable_key'] = $validated['publishable_key'] ?? null;
        $config['secret_key'] = $validated['secret_key'] ?? null;
        $config['webhook_secret'] = $validated['webhook_secret'] ?? null;

        $stripeProvider->enabled = $enabled;
        $stripeProvider->config = $config;
        $stripeProvider->save();

        return redirect()
            ->route('admin.settings.payments.stripe')
            ->with('success', 'Configuration Stripe mise à jour avec succès.');
    }

    /**
     * Toggle on/off des providers (modules, etc.)
     */
    public function toggle(PaymentProvider $paymentProvider)
    {
        if ($paymentProvider->code === 'stripe') {
            return back()->with('error', 'Stripe est géré depuis son écran dédié.');
        }

        $paymentProvider->enabled = ! $paymentProvider->enabled;
        $paymentProvider->save();

        return back()->with('success', 'Moyen de paiement mis à jour.');
    }
}
