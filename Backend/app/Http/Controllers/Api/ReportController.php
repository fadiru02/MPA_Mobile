<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ReportProfitLossRequest;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Throwable;

class ReportController extends ApiController
{
    public function profitLoss(
        ReportProfitLossRequest $request,
        ReportService $service,
    ): JsonResponse {
        $filters = $request->validated();

        $start = CarbonImmutable::parse($filters['start_date'])->startOfDay();
        $end = CarbonImmutable::parse($filters['end_date'])->startOfDay();
        $groupBy = (string) ($filters['group_by'] ?? 'day');

        try {
            $report = $service->profitLoss($start, $end, $groupBy);

            return $this->success($report, 'Laporan laba rugi.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->error(
                'Terjadi kesalahan saat menyusun laporan.',
                500,
                [],
                'SERVER_ERROR',
            );
        }
    }
}
