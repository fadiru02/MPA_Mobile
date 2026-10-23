<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_create_a_table_order_and_stock_is_reduced(): void
    {
        $product = Product::factory()->create([
            'nama' => 'Nasi Kucing',
            'stok' => 10,
            'harga_modal' => 3_000,
            'harga_jual' => 5_000,
        ]);

        $response = $this->postJson('/api/orders', [
            'nomor_meja' => '12',
            'items' => [
                ['product_id' => $product->id, 'qty' => 2],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status_pembayaran', 'pending')
            ->assertJsonPath('data.nomor_meja', '12')
            ->assertJsonPath('data.total_omset', 10_000)
            ->assertJsonPath('data.items.0.subtotal', 10_000)
            ->assertJsonPath('data.nomor_invoice', 'INV-'.now()->format('Ymd').'-0001')
            ->assertJsonMissingPath('data.total_hpp')
            ->assertJsonMissingPath('data.laba_kotor');

        $this->assertSame(8, $product->fresh()->stok);

        $this->assertDatabaseHas('orders', [
            'total_hpp' => 6_000,
            'total_omset' => 10_000,
            'laba_kotor' => 4_000,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'qty' => 2,
            'harga_satuan' => 5_000,
            'harga_modal_satuan' => 3_000,
            'subtotal' => 10_000,
            'subtotal_hpp' => 6_000,
        ]);
    }

    public function test_guest_order_requires_a_table_number(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('nomor_meja');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_authenticated_cashier_can_create_an_order_without_a_table_number(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $product = Product::factory()->create(['stok' => 5]);

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nomor_meja', null)
            ->assertJsonPath('data.total_hpp', $product->harga_modal)
            ->assertJsonPath('data.laba_kotor', $product->harga_jual - $product->harga_modal);
    }

    public function test_order_is_rolled_back_when_stock_is_insufficient(): void
    {
        $product = Product::factory()->create([
            'nama' => 'Sate Usus',
            'stok' => 1,
        ]);

        $response = $this->postJson('/api/orders', [
            'nomor_meja' => '3',
            'items' => [
                ['product_id' => $product->id, 'qty' => 2],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INSUFFICIENT_STOCK');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(1, $product->fresh()->stok);
    }

    public function test_inactive_product_cannot_be_ordered(): void
    {
        $product = Product::factory()->inactive()->create();

        $response = $this->postJson('/api/orders', [
            'nomor_meja' => '4',
            'items' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'PRODUCT_UNAVAILABLE');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_duplicate_client_request_id_returns_the_same_order(): void
    {
        $product = Product::factory()->create(['stok' => 10]);
        $payload = [
            'nomor_meja' => '7',
            'client_request_id' => (string) Str::uuid(),
            'items' => [
                ['product_id' => $product->id, 'qty' => 2],
            ],
        ];

        $first = $this->postJson('/api/orders', $payload);
        $second = $this->postJson('/api/orders', $payload);

        $first->assertCreated();
        $second->assertOk();

        $this->assertSame(
            $first->json('data.nomor_invoice'),
            $second->json('data.nomor_invoice'),
        );

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(8, $product->fresh()->stok);
    }

    public function test_multiple_products_are_totalled_correctly(): void
    {
        $first = Product::factory()->create(['stok' => 10, 'harga_modal' => 1_000, 'harga_jual' => 2_500]);
        $second = Product::factory()->create(['stok' => 10, 'harga_modal' => 2_000, 'harga_jual' => 4_000]);

        $response = $this->postJson('/api/orders', [
            'nomor_meja' => 'A1',
            'items' => [
                ['product_id' => $first->id, 'qty' => 2],
                ['product_id' => $second->id, 'qty' => 3],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.total_omset', 17_000);

        $this->assertDatabaseHas('orders', [
            'total_omset' => 17_000,
            'total_hpp' => 8_000,
            'laba_kotor' => 9_000,
        ]);
    }

    public function test_unknown_product_is_rejected_by_validation(): void
    {
        $response = $this->postJson('/api/orders', [
            'nomor_meja' => '2',
            'items' => [
                ['product_id' => 999, 'qty' => 1],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.product_id');
    }
}
