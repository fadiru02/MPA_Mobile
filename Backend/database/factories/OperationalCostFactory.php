<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OperationalCost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OperationalCost>
 */
class OperationalCostFactory extends Factory
{
    /**
     * @var class-string<OperationalCost>
     */
    protected $model = OperationalCost::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal' => now()->toDateString(),
            'kategori' => fake()->randomElement(['arang', 'es_batu', 'sewa', 'listrik', 'lainnya']),
            'keterangan' => fake()->sentence(3),
            'nominal' => fake()->numberBetween(5_000, 100_000),
        ];
    }
}
