<?php

namespace App\Modules\Commerce\Http\Controllers;

use App\Modules\Commerce\Actions\RecordPaymentNotification;
use App\Modules\Commerce\Gateways\PaymentGatewayResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController
{
    public function __invoke(Request $request, string $gateway, PaymentGatewayResolver $gateways, RecordPaymentNotification $record): JsonResponse
    {
        $driver = $gateways->for($gateway);
        abort_unless($driver->verify($request), 403);

        $payment = $record->handle($gateway, $driver->parse($request->all()), $request->all());
        abort_if($payment === null, 404);

        return response()->json(['ok' => true]);
    }
}
