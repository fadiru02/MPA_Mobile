<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\OperationalCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OperationalCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_record_an_operational_cost(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/operational-costs', [
            'tanggal' => '2026-10-22',
            'kategori' => 'arang',
            'keterangan' => 'Beli arang 2 karung',
            'nominal' => 25_000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.kategori', 'arang')
            ->assertJsonPath('data.nominal', 25_000);

        $this->assertDatabaseHas('operational_costs', [
            'nominal' => 25_000,
        ]);

        $cost = OperationalCost::query()->firstOrFail();

        $this->assertSame('2026-10-22', $cost->tanggal->toDateString());
    }

    public function test_operational_cost_requires_a_positive_integer_nominal(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/operational-costs', [
            'tanggal' => '2026-10-22',
            'kategori' => 'es_batu',
            'nominal' => 0,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('nominal');
    }

    public function test_guest_cannot_record_an_operational_cost(): void
    {
        $this->postJson('/api/operational-costs', [
            'tanggal' => '2026-10-22',
            'kategori' => 'sewa',
            'nominal' => 50_000,
        ])->assertUnauthorized();
    }

    public function test_operational_costs_can_be_filtered_by_date(): void
    {
        Sanctum::actingAs(User::factory()->create());

        OperationalCost::factory()->create(['tanggal' => '2026-10-01', 'nominal' => 10_000]);
        OperationalCost::factory()->create(['tanggal' => '2026-10-05', 'nominal' => 20_000]);

        $this->getJson('/api/operational-costs?start_date=2026-10-05&end_date=2026-10-05')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nominal', 20_000);
    }
}
