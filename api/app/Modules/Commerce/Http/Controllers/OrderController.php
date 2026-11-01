<?php

namespace App\Modules\Commerce\Http\Controllers;

use App\Modules\Catalog\Models\Cohort;
use App\Modules\Catalog\Models\Program;
use App\Modules\Commerce\Actions\ApplyVoucher;
use App\Modules\Commerce\Actions\ChargeOrder;
use App\Modules\Commerce\Actions\CreateOrder;
use App\Modules\Commerce\Actions\RecordPaymentNotification;
use App\Modules\Commerce\Enums\PaymentMethod;
use App\Modules\Commerce\Gateways\SimulatorGateway;
use App\Modules\Commerce\Http\Resources\OrderResource;
use App\Modules\Commerce\Models\Order;
use App\Support\ApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class OrderController
{
    private const RELATIONS = ['items.program', 'items.cohort', 'voucher'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $request->user()->orders()->with(self::RELATIONS)->latest()->limit(20)->get();

        return OrderResource::collection($orders);
    }

    public function store(Request $request, CreateOrder $createOrder): JsonResponse
    {
        $data = $request->validate([
            'program' => ['required', 'string'],
            'cohort_id' => ['nullable', 'integer'],
        ]);

        $program = Program::where('slug', $data['program'])->firstOrFail();
        $cohort = isset($data['cohort_id']) ? Cohort::findOrFail($data['cohort_id']) : null;

        $order = $createOrder->handle($request->user(), $program, $cohort);

        return (new OrderResource($order->load(self::RELATIONS)))
            ->response()
            ->setStatusCode($order->wasRecentlyCreated ? 201 : 200);
    }

    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return new OrderResource($order->load(self::RELATIONS));
    }

    public function applyVoucher(Request $request, Order $order, ApplyVoucher $applyVoucher): OrderResource
    {
        Gate::authorize('update', $order);
        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);

        return new OrderResource($applyVoucher->handle($order, $data['code'])->load(self::RELATIONS));
    }

    public function removeVoucher(Order $order, ApplyVoucher $applyVoucher): OrderResource
    {
        Gate::authorize('update', $order);

        return new OrderResource($applyVoucher->remove($order)->load(self::RELATIONS));
    }

    public function pay(Request $request, Order $order, ChargeOrder $chargeOrder): OrderResource
    {
        Gate::authorize('update', $order);
        $data = $request->validate([
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
        ]);

        $order = $chargeOrder->handle($order, PaymentMethod::from($data['method']), $data['customer_name'], $data['customer_phone']);

        return new OrderResource($order->load(self::RELATIONS));
    }

    /**
     * Hanya untuk lokal/staging: memalsukan notifikasi settlement lewat jalur webhook yang sama.
     */
    public function simulatePayment(Order $order, SimulatorGateway $simulator, RecordPaymentNotification $record): OrderResource
    {
        Gate::authorize('update', $order);

        if (config('payment.default') !== 'simulator' || ! config('payment.simulator.enabled')) {
            throw new ApiException('Simulator pembayaran tidak aktif.', 'simulator_disabled', 404);
        }

        if ($order->payment_instruction === null) {
            throw new ApiException('Pilih metode bayar dulu.', 'order_not_charged', 409);
        }

        $payload = $simulator->settlementPayload($order);
        $record->handle($simulator->name(), $simulator->parse($payload), $payload);

        return new OrderResource($order->refresh()->load(self::RELATIONS));
    }
}
