<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\OperationalCost;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfitLossReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_calculates_gross_and_net_profit_for_paid_orders_only(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->createOrder('2026-10-01 10:00:00', PaymentStatus::Paid, 100_000, 60_000);
        $this->createOrder('2026-10-02 11:00:00', PaymentStatus::Paid, 50_000, 30_000);
        $this->createOrder('2026-10-01 12:00:00', PaymentStatus::Pending, 999_999, 1);
        $this->createOrder('2026-10-02 13:00:00', PaymentStatus::Cancelled, 777_777, 1);

        OperationalCost::factory()->create(['tanggal' => '2026-10-01', 'nominal' => 15_000]);
        OperationalCost::factory()->create(['tanggal' => '2026-10-02', 'nominal' => 5_000]);

        $response = $this->getJson(
            '/api/reports/profit-loss?start_date=2026-10-01&end_date=2026-10-02&group_by=day',
        );

        $response->assertOk()
            ->assertJsonPath('data.ringkasan.jumlah_transaksi', 2)
            ->assertJsonPath('data.ringkasan.total_omset', 150_000)
            ->assertJsonPath('data.ringkasan.total_hpp', 90_000)
            ->assertJsonPath('data.ringkasan.laba_kotor', 60_000)
            ->assertJsonPath('data.ringkasan.total_biaya_operasional', 20_000)
            ->assertJsonPath('data.ringkasan.laba_bersih', 40_000)
            ->assertJsonCount(2, 'data.rincian')
            ->assertJsonPath('data.rincian.0.periode', '2026-10-01')
            ->assertJsonPath('data.rincian.0.laba_kotor', 40_000)
            ->assertJsonPath('data.rincian.0.total_biaya_operasional', 15_000)
            ->assertJsonPath('data.rincian.0.laba_bersih', 25_000)
            ->assertJsonPath('data.rincian.1.periode', '2026-10-02')
            ->assertJsonPath('data.rincian.1.laba_bersih', 15_000);
    }

    public function test_report_can_group_by_month(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->createOrder('2026-10-01 10:00:00', PaymentStatus::Paid, 100_000, 60_000);
        $this->createOrder('2026-10-20 11:00:00', PaymentStatus::Paid, 50_000, 30_000);

        OperationalCost::factory()->create(['tanggal' => '2026-10-05', 'nominal' => 10_000]);

        $response = $this->getJson(
            '/api/reports/profit-loss?start_date=2026-10-01&end_date=2026-10-31&group_by=month',
        );

        $response->assertOk()
            ->assertJsonPath('data.periode.group_by', 'month')
            ->assertJsonCount(1, 'data.rincian')
            ->assertJsonPath('data.rincian.0.periode', '2026-10')
            ->assertJsonPath('data.rincian.0.total_omset', 150_000)
            ->assertJsonPath('data.rincian.0.laba_bersih', 50_000);
    }

    public function test_report_respects_the_requested_date_range(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->createOrder('2026-10-01 10:00:00', PaymentStatus::Paid, 100_000, 60_000);
        $this->createOrder('2026-10-02 11:00:00', PaymentStatus::Paid, 50_000, 30_000);

        $response = $this->getJson(
            '/api/reports/profit-loss?start_date=2026-10-02&end_date=2026-10-02',
        );

        $response->assertOk()
            ->assertJsonPath('data.ringkasan.jumlah_transaksi', 1)
            ->assertJsonPath('data.ringkasan.total_omset', 50_000)
            ->assertJsonPath('data.ringkasan.laba_kotor', 20_000)
            ->assertJsonPath('data.ringkasan.laba_bersih', 20_000)
            ->assertJsonCount(1, 'data.rincian');
    }

    public function test_report_rejects_an_invalid_or_excessive_date_range(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/reports/profit-loss?start_date=2026-10-31&end_date=2026-10-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('end_date');

        $this->getJson('/api/reports/profit-loss?start_date=2025-01-01&end_date=2026-10-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('end_date');
    }

    public function test_guest_cannot_access_the_report(): void
    {
        $this->getJson('/api/reports/profit-loss?start_date=2026-10-01&end_date=2026-10-01')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    private function createOrder(
        string $paidAt,
        PaymentStatus $status,
        int $omset,
        int $hpp,
    ): Order {
        return Order::factory()->create([
            'status_pembayaran' => $status,
            'metode_pembayaran' => $status === PaymentStatus::Paid ? PaymentMethod::Cash : null,
            'paid_at' => $paidAt,
            'total_omset' => $omset,
            'total_hpp' => $hpp,
            'laba_kotor' => $omset - $hpp,
        ]);
    }
}
