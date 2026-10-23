<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_only_returns_active_products_without_cost_data(): void
    {
        $active = Product::factory()->create([
            'nama' => 'Wedang Jahe',
            'harga_modal' => 1_500,
            'harga_jual' => 4_000,
        ]);
        Product::factory()->inactive()->create(['nama' => 'Menu Lama']);

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id)
            ->assertJsonPath('data.0.nama', 'Wedang Jahe')
            ->assertJsonPath('data.0.harga_jual', 4_000)
            ->assertJsonMissingPath('data.0.harga_modal');
    }

    public function test_catalog_can_be_filtered_by_category(): void
    {
        Product::factory()->create(['nama' => 'Nasi Kucing', 'kategori' => 'makanan']);
        Product::factory()->create(['nama' => 'Teh Manis', 'kategori' => 'minuman']);

        $response = $this->getJson('/api/products?kategori=minuman');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Teh Manis');
    }

    public function test_catalog_rejects_an_unknown_category(): void
    {
        $this->getJson('/api/products?kategori=makanan_berat')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('kategori');
    }
}
