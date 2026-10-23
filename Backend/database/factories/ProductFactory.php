<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductCategory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @var class-string<Product>
     */
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hargaModal = fake()->numberBetween(1_000, 10_000);

        return [
            'nama' => fake()->unique()->words(2, true),
            'kategori' => fake()->randomElement(ProductCategory::cases()),
            'stok' => fake()->numberBetween(0, 100),
            'track_stock' => true,
            'harga_modal' => $hargaModal,
            'harga_jual' => $hargaModal + fake()->numberBetween(500, 5_000),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'stok' => 0,
        ]);
    }
}
