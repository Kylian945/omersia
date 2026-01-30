<?php

declare(strict_types=1);
// app/Payments/PaymentProviderManager.php

namespace App\Payments;

use App\Payments\Contracts\PaymentProvider;
use App\Payments\Providers\StripePaymentProvider;
use InvalidArgumentException;
use Omersia\Payment\Models\PaymentProvider as PaymentProviderModel;

class PaymentProviderManager
{
    public function resolve(string $code): PaymentProvider
    {
        /** @var PaymentProviderModel|null $config */
        $config = PaymentProviderModel::where('code', $code)
            ->where('enabled', true)
            ->first();

        if (! $config) {
            throw new InvalidArgumentException("Payment provider [$code] not found or disabled.");
        }

        return match ($code) {
            'stripe' => new StripePaymentProvider($config),
            // 'paypal' => new PaypalPaymentProvider($config),
            // 'mollie' => new MolliePaymentProvider($config),
            default => throw new InvalidArgumentException("Payment provider [$code] not implemented."),
        };
    }
}
