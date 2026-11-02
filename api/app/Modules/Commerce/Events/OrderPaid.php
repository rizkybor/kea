<?php

namespace App\Modules\Commerce\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class OrderPaid implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $orderId) {}
}
