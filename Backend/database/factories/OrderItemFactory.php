<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * @var class-string<OrderItem>
     */
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'nama_produk' => fake()->words(2, true),
            'qty' => 1,
            'harga_satuan' => 5_000,
            'harga_modal_satuan' => 3_000,
            'subtotal' => 5_000,
            'subtotal_hpp' => 3_000,
        ];
    }
}
