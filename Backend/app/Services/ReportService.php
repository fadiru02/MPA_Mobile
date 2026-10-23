<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\OperationalCost;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ReportService
{
    /**
     * @return array<string, mixed>
     */
    public function profitLoss(CarbonImmutable $start, CarbonImmutable $end, string $groupBy): array
    {
        if (! in_array($groupBy, ['day', 'month'], true)) {
            throw new InvalidArgumentException('Parameter group_by harus bernilai day atau month.');
        }

        $endExclusive = $end->addDay()->startOfDay();
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();
        $costEndExclusive = $end->addDay()->toDateString();

        $summary = Order::query()
            ->where('status_pembayaran', PaymentStatus::Paid)
            ->where('paid_at', '>=', $start)
            ->where('paid_at', '<', $endExclusive)
            ->selectRaw('COUNT(*) as jumlah_transaksi')
            ->selectRaw('COALESCE(SUM(total_omset), 0) as total_omset')
            ->selectRaw('COALESCE(SUM(total_hpp), 0) as total_hpp')
            ->selectRaw('COALESCE(SUM(laba_kotor), 0) as laba_kotor')
            ->first();

        $jumlahTransaksi = (int) ($summary?->getAttribute('jumlah_transaksi') ?? 0);
        $totalOmset = (int) ($summary?->getAttribute('total_omset') ?? 0);
        $totalHpp = (int) ($summary?->getAttribute('total_hpp') ?? 0);
        $labaKotor = (int) ($summary?->getAttribute('laba_kotor') ?? 0);

        $totalOperational = (int) OperationalCost::query()
            ->where('tanggal', '>=', $startDate)
            ->where('tanggal', '<', $costEndExclusive)
            ->sum('nominal');

        [$orderPeriodExpression, $costPeriodExpression] = $this->periodExpressions($groupBy);

        $revenueRows = Order::query()
            ->where('status_pembayaran', PaymentStatus::Paid)
            ->where('paid_at', '>=', $start)
            ->where('paid_at', '<', $endExclusive)
            ->selectRaw("{$orderPeriodExpression} as periode, COUNT(*) as jumlah_transaksi")
            ->selectRaw('COALESCE(SUM(total_omset), 0) as total_omset')
            ->selectRaw('COALESCE(SUM(total_hpp), 0) as total_hpp')
            ->selectRaw('COALESCE(SUM(laba_kotor), 0) as laba_kotor')
            ->groupByRaw($orderPeriodExpression)
            ->orderBy('periode')
            ->get()
            ->keyBy(fn (Order $row): string => (string) $row->getAttribute('periode'));

        $costRows = OperationalCost::query()
            ->where('tanggal', '>=', $startDate)
            ->where('tanggal', '<', $costEndExclusive)
            ->selectRaw("{$costPeriodExpression} as periode")
            ->selectRaw('COALESCE(SUM(nominal), 0) as total_biaya_operasional')
            ->groupByRaw($costPeriodExpression)
            ->orderBy('periode')
            ->get()
            ->keyBy(fn (OperationalCost $row): string => (string) $row->getAttribute('periode'));

        $periods = $revenueRows->keys()
            ->merge($costRows->keys())
            ->unique()
            ->sort()
            ->values();

        $breakdown = $periods->map(function (string $period) use ($revenueRows, $costRows): array {
            $revenue = $revenueRows->get($period);
            $cost = $costRows->get($period);

            $omset = (int) ($revenue?->getAttribute('total_omset') ?? 0);
            $hpp = (int) ($revenue?->getAttribute('total_hpp') ?? 0);
            $grossProfit = (int) ($revenue?->getAttribute('laba_kotor') ?? 0);
            $operational = (int) ($cost?->getAttribute('total_biaya_operasional') ?? 0);

            return [
                'periode' => $period,
                'jumlah_transaksi' => (int) ($revenue?->getAttribute('jumlah_transaksi') ?? 0),
                'total_omset' => $omset,
                'total_hpp' => $hpp,
                'laba_kotor' => $grossProfit,
                'total_biaya_operasional' => $operational,
                'laba_bersih' => $grossProfit - $operational,
            ];
        })->all();

        return [
            'periode' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'group_by' => $groupBy,
                'timezone' => (string) config('app.timezone'),
            ],
            'ringkasan' => [
                'jumlah_transaksi' => $jumlahTransaksi,
                'total_omset' => $totalOmset,
                'total_hpp' => $totalHpp,
                'laba_kotor' => $labaKotor,
                'total_biaya_operasional' => $totalOperational,
                'laba_bersih' => $labaKotor - $totalOperational,
            ],
            'rincian' => $breakdown,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function periodExpressions(string $groupBy): array
    {
        $driver = DB::connection()->getDriverName();

        if ($groupBy === 'month') {
            return $driver === 'sqlite'
                ? ["strftime('%Y-%m', paid_at)", "strftime('%Y-%m', tanggal)"]
                : ["DATE_FORMAT(paid_at, '%Y-%m')", "DATE_FORMAT(tanggal, '%Y-%m')"];
        }

        return ['DATE(paid_at)', 'DATE(tanggal)'];
    }
}
