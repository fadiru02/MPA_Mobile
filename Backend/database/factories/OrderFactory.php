<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @var class-string<Order>
     */
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nomor_invoice' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'nomor_meja' => fake()->optional()->numberBetween(1, 20),
            'status_pembayaran' => PaymentStatus::Pending,
            'metode_pembayaran' => null,
            'total_hpp' => 0,
            'total_omset' => 0,
            'laba_kotor' => 0,
            'client_request_id' => null,
            'catatan' => null,
            'paid_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status_pembayaran' => PaymentStatus::Paid,
            'metode_pembayaran' => PaymentMethod::Cash,
            'paid_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status_pembayaran' => PaymentStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
