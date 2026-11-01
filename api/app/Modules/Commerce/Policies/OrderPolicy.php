<?php

namespace App\Modules\Commerce\Policies;

use App\Modules\Commerce\Models\Order;
use App\Modules\Identity\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }

    public function update(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }
}
