<?php

declare(strict_types=1);

namespace Omersia\Api\Services;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Omersia\Customer\Models\Customer;

final class CartCustomerResolver
{
    public function resolve(Request $request, ?int $customerId = null): ?Customer
    {
        $user = $request->user();
        if ($user instanceof Customer) {
            return $user;
        }

        if ($token = $request->bearerToken()) {
            if ($accessToken = PersonalAccessToken::findToken($token)) {
                $tokenable = $accessToken->tokenable;
                if ($tokenable instanceof Customer) {
                    return $tokenable;
                }
            }
        }

        if ($customerId) {
            return Customer::where('id', $customerId)->first();
        }

        return null;
    }
}
