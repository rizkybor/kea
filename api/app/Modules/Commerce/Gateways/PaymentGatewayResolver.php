<?php

namespace App\Modules\Commerce\Gateways;

use App\Modules\Commerce\Contracts\PaymentGateway;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentGatewayResolver
{
    /**
     * @var array<string, class-string<PaymentGateway>>
     */
    private const DRIVERS = [
        'midtrans' => MidtransGateway::class,
        'simulator' => SimulatorGateway::class,
    ];

    public function __construct(private Container $container) {}

    public function for(string $name): PaymentGateway
    {
        $driver = self::DRIVERS[$name] ?? throw new NotFoundHttpException("Gateway [{$name}] tidak dikenal.");

        if ($name === 'simulator' && ! config('payment.simulator.enabled')) {
            throw new NotFoundHttpException('Simulator pembayaran tidak aktif.');
        }

        return $this->container->make($driver);
    }

    public function default(): PaymentGateway
    {
        return $this->for(config('payment.default'));
    }
}
