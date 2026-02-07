<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;
use Omersia\Customer\Models\Customer;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin.dashboard', function ($user) {
    return $user instanceof User;
});

Broadcast::channel('admin.orders', function ($user) {
    if (! ($user instanceof User)) {
        return false;
    }

    return $user->can('orders.view');
});

Broadcast::channel('admin.products', function ($user) {
    if (! ($user instanceof User)) {
        return false;
    }

    return $user->can('products.view');
});

Broadcast::channel('admin.gdpr', function ($user) {
    if (! ($user instanceof User)) {
        return false;
    }

    return $user->can('settings.view');
});

Broadcast::channel('customer.orders.{customerId}', function ($user, int $customerId) {
    if (! ($user instanceof Customer)) {
        return false;
    }

    return (int) $user->id === $customerId;
});

Broadcast::channel('customer.gdpr.{customerId}', function ($user, int $customerId) {
    if (! ($user instanceof Customer)) {
        return false;
    }

    return (int) $user->id === $customerId;
});
