<?php

namespace Tests\Feature;

use App\Modules\Commerce\Enums\OrderStatus;
use App\Modules\Commerce\Enums\VoucherType;
use App\Modules\Commerce\Gateways\SimulatorGateway;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Voucher;
use App\Modules\Learning\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsCatalog;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use BuildsCatalog, RefreshDatabase;

    public function test_learner_can_checkout_with_voucher_and_gets_enrolled_after_payment(): void
    {
        $program = $this->program();
        $cohort = $this->cohort($program);
        $voucher = Voucher::create(['code' => 'KEAHEMAT', 'type' => VoucherType::Percent, 'value' => 10, 'max_discount' => 300_000]);
        $learner = $this->user();
        Sanctum::actingAs($learner);

        $uuid = $this->postJson('/api/v1/orders', ['program' => $program->slug, 'cohort_id' => $cohort->id])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.discount', 0)
            ->assertJsonPath('data.total', 4_000_000)
            ->json('data.uuid');

        $this->assertSame(1, $cohort->fresh()->seats_taken);

        $this->postJson("/api/v1/orders/{$uuid}/vouchers", ['code' => 'keahemat'])
            ->assertOk()
            ->assertJsonPath('data.discount', 300_000)
            ->assertJsonPath('data.total', 3_700_000);

        $this->postJson("/api/v1/orders/{$uuid}/pay", ['method' => 'va', 'customer_name' => 'Sari', 'customer_phone' => '081234567890'])
            ->assertOk()
            ->assertJsonPath('data.payment_instruction.method', 'va')
            ->assertJsonPath('data.status', 'pending');

        $this->postJson("/api/v1/orders/{$uuid}/simulate-payment")
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('enrollments', ['user_id' => $learner->id, 'program_id' => $program->id, 'cohort_id' => $cohort->id, 'status' => 'active']);
        $this->assertSame(1, $voucher->fresh()->used_count);
        $this->assertSame(1, $cohort->fresh()->seats_taken);
    }

    public function test_repeated_checkout_reuses_the_pending_order(): void
    {
        $program = $this->program();
        $cohort = $this->cohort($program);
        Sanctum::actingAs($this->user());

        $first = $this->postJson('/api/v1/orders', ['program' => $program->slug, 'cohort_id' => $cohort->id])->assertCreated()->json('data.uuid');
        $second = $this->postJson('/api/v1/orders', ['program' => $program->slug, 'cohort_id' => $cohort->id])->assertOk()->json('data.uuid');

        $this->assertSame($first, $second);
        $this->assertSame(1, $cohort->fresh()->seats_taken);
    }

    public function test_full_cohort_is_rejected_with_conflict(): void
    {
        $program = $this->program();
        $cohort = $this->cohort($program, ['quota' => 1, 'seats_taken' => 1]);
        Sanctum::actingAs($this->user());

        $this->postJson('/api/v1/orders', ['program' => $program->slug, 'cohort_id' => $cohort->id])
            ->assertStatus(409)
            ->assertJsonPath('code', 'cohort_full');
    }

    public function test_unverified_user_cannot_checkout(): void
    {
        $program = $this->program();
        $cohort = $this->cohort($program);
        Sanctum::actingAs($this->user(verified: false));

        $this->postJson('/api/v1/orders', ['program' => $program->slug, 'cohort_id' => $cohort->id])
            ->assertForbidden()
            ->assertJsonPath('code', 'email_unverified');
    }

    public function test_duplicate_webhooks_create_a_single_enrollment(): void
    {
        $program = $this->program();
        $cohort = $this->cohort($program);
        Sanctum::actingAs($this->user());

        $uuid = $this->postJson('/api/v1/orders', ['program' => $program->slug, 'cohort_id' => $cohort->id])->json('data.uuid');
        $this->postJson("/api/v1/orders/{$uuid}/pay", ['method' => 'qris', 'customer_name' => 'Sari', 'customer_phone' => '081234567890'])->assertOk();

        $simulator = app(SimulatorGateway::class);
        $body = json_encode($simulator->settlementPayload(Order::where('uuid', $uuid)->firstOrFail()));

        foreach ([1, 2] as $attempt) {
            $this->call('POST', '/api/v1/webhooks/simulator', [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_SIMULATOR_SIGNATURE' => $simulator->sign($body),
            ], $body)->assertOk();
        }

        $this->assertSame(1, Enrollment::count());
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        $this->postJson('/api/v1/webhooks/simulator', ['order_id' => 'x', 'transaction_id' => 'y', 'gross_amount' => '1', 'transaction_status' => 'settlement'], ['X-Simulator-Signature' => 'salah'])
            ->assertForbidden();
    }

    public function test_underpaid_settlement_does_not_mark_order_paid(): void
    {
        $program = $this->program();
        $cohort = $this->cohort($program);
        Sanctum::actingAs($this->user());

        $uuid = $this->postJson('/api/v1/orders', ['program' => $program->slug, 'cohort_id' => $cohort->id])->json('data.uuid');
        $this->postJson("/api/v1/orders/{$uuid}/pay", ['method' => 'va', 'customer_name' => 'Sari', 'customer_phone' => '081234567890']);

        $simulator = app(SimulatorGateway::class);
        $payload = ['gross_amount' => '1000'] + $simulator->settlementPayload(Order::where('uuid', $uuid)->firstOrFail());
        $body = json_encode($payload);

        $this->call('POST', '/api/v1/webhooks/simulator', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_SIMULATOR_SIGNATURE' => $simulator->sign($body),
        ], $body)->assertOk();

        $this->assertSame(OrderStatus::Pending, Order::where('uuid', $uuid)->first()->status);
        $this->assertSame(0, Enrollment::count());
    }

    public function test_expired_reservation_releases_the_seat(): void
    {
        $program = $this->program();
        $cohort = $this->cohort($program);
        Sanctum::actingAs($this->user());

        $uuid = $this->postJson('/api/v1/orders', ['program' => $program->slug, 'cohort_id' => $cohort->id])->json('data.uuid');
        $this->travel(31)->minutes();

        $this->artisan('orders:expire-reservations')->assertSuccessful();

        $this->assertSame(OrderStatus::Expired, Order::where('uuid', $uuid)->first()->status);
        $this->assertSame(0, $cohort->fresh()->seats_taken);
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $program = $this->program();
        $cohort = $this->cohort($program);
        Sanctum::actingAs($this->user());
        $uuid = $this->postJson('/api/v1/orders', ['program' => $program->slug, 'cohort_id' => $cohort->id])->json('data.uuid');

        Sanctum::actingAs($this->user());

        $this->getJson("/api/v1/orders/{$uuid}")->assertForbidden();
    }
}
