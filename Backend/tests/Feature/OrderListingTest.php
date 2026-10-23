<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_list_and_filter_orders(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Order::factory()->create(['nomor_invoice' => 'INV-TEST-0001']);
        Order::factory()->paid()->create(['nomor_invoice' => 'INV-TEST-0002']);

        $response = $this->getJson('/api/orders?status=paid&per_page=1');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.nomor_invoice', 'INV-TEST-0002')
            ->assertJsonPath('data.data.0.status_pembayaran', 'paid')
            ->assertJsonPath('data.data.0.metode_pembayaran', 'cash');
    }

    public function test_guest_cannot_list_orders(): void
    {
        $this->getJson('/api/orders')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }
}
