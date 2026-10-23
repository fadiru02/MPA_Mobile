<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_pay_a_pending_order(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $product = Product::factory()->create([
            'stok' => 10,
            'harga_modal' => 3_000,
            'harga_jual' => 5_000,
        ]);

        $orderId = $this->createPendingOrder($product, 2);

        $response = $this->postJson("/api/orders/{$orderId}/pay", [
            'metode_pembayaran' => 'qris',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status_pembayaran', 'paid')
            ->assertJsonPath('data.metode_pembayaran', 'qris')
            ->assertJsonPath('data.total_omset', 10_000)
            ->assertJsonPath('data.total_hpp', 6_000)
            ->assertJsonPath('data.laba_kotor', 4_000);

        $this->assertNotNull($response->json('data.paid_at'));
        $this->assertSame(8, $product->fresh()->stok);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status_pembayaran' => PaymentStatus::Paid->value,
            'metode_pembayaran' => 'qris',
            'total_omset' => 10_000,
            'laba_kotor' => 4_000,
        ]);
    }

    public function test_payment_uses_frozen_item_snapshots_even_if_product_price_changes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $product = Product::factory()->create([
            'stok' => 10,
            'harga_modal' => 3_000,
            'harga_jual' => 5_000,
        ]);

        $orderId = $this->createPendingOrder($product, 2);

        $product->update([
            'harga_modal' => 9_000,
            'harga_jual' => 12_000,
        ]);

        $response = $this->postJson("/api/orders/{$orderId}/pay", [
            'metode_pembayaran' => 'cash',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.total_omset', 10_000)
            ->assertJsonPath('data.total_hpp', 6_000)
            ->assertJsonPath('data.laba_kotor', 4_000);
    }

    public function test_paid_order_cannot_be_paid_twice(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $product = Product::factory()->create(['stok' => 10]);
        $orderId = $this->createPendingOrder($product, 1);

        $this->postJson("/api/orders/{$orderId}/pay", ['metode_pembayaran' => 'cash'])
            ->assertOk();

        $paidAt = Order::query()->findOrFail($orderId)->paid_at;

        $this->postJson("/api/orders/{$orderId}/pay", ['metode_pembayaran' => 'qris'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'INVALID_ORDER_STATE');

        $order = Order::query()->findOrFail($orderId);

        $this->assertTrue($order->isPaid());
        $this->assertTrue($order->paid_at->equalTo($paidAt));
        $this->assertSame(9, $product->fresh()->stok);
    }

    public function test_payment_method_is_required_and_validated(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $product = Product::factory()->create();
        $orderId = $this->createPendingOrder($product, 1);

        $this->postJson("/api/orders/{$orderId}/pay", [
            'metode_pembayaran' => 'transfer',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('metode_pembayaran');
    }

    public function test_guest_cannot_pay_an_order(): void
    {
        $product = Product::factory()->create();
        $orderId = $this->createPendingOrder($product, 1);

        $this->postJson("/api/orders/{$orderId}/pay", [
            'metode_pembayaran' => 'cash',
        ])->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    private function createPendingOrder(Product $product, int $qty): int
    {
        $response = $this->postJson('/api/orders', [
            'nomor_meja' => '5',
            'items' => [
                ['product_id' => $product->id, 'qty' => $qty],
            ],
        ]);

        $response->assertCreated();

        return (int) $response->json('data.id');
    }
}
