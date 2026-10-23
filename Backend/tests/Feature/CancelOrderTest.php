<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CancelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_a_pending_order_restores_stock_once(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $product = Product::factory()->create(['stok' => 10]);
        $orderId = $this->createPendingOrder($product, 3);

        $this->assertSame(7, $product->fresh()->stok);

        $this->postJson("/api/orders/{$orderId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status_pembayaran', 'cancelled');

        $this->assertSame(10, $product->fresh()->stok);

        $this->postJson("/api/orders/{$orderId}/cancel")
            ->assertStatus(409)
            ->assertJsonPath('code', 'INVALID_ORDER_STATE');

        $this->assertSame(10, $product->fresh()->stok);
    }

    public function test_paid_order_cannot_be_cancelled(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $product = Product::factory()->create(['stok' => 10]);
        $orderId = $this->createPendingOrder($product, 2);

        $this->postJson("/api/orders/{$orderId}/pay", ['metode_pembayaran' => 'cash'])
            ->assertOk();

        $this->postJson("/api/orders/{$orderId}/cancel")
            ->assertStatus(409)
            ->assertJsonPath('code', 'INVALID_ORDER_STATE');

        $this->assertSame(8, $product->fresh()->stok);
    }

    public function test_restoring_stock_also_works_for_a_soft_deleted_product(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $product = Product::factory()->create(['stok' => 4]);
        $orderId = $this->createPendingOrder($product, 2);

        $product->delete();

        $this->postJson("/api/orders/{$orderId}/cancel")->assertOk();

        $this->assertSame(4, Product::withTrashed()->findOrFail($product->id)->stok);
    }

    private function createPendingOrder(Product $product, int $qty): int
    {
        $response = $this->postJson('/api/orders', [
            'nomor_meja' => '8',
            'items' => [
                ['product_id' => $product->id, 'qty' => $qty],
            ],
        ]);

        $response->assertCreated();

        return (int) $response->json('data.id');
    }
}
