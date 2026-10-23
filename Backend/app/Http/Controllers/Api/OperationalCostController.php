<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreOperationalCostRequest;
use App\Http\Resources\OperationalCostResource;
use App\Models\OperationalCost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class OperationalCostController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $costs = OperationalCost::query()
            ->when(
                isset($filters['start_date']),
                fn ($query) => $query->whereDate('tanggal', '>=', $filters['start_date']),
            )
            ->when(
                isset($filters['end_date']),
                fn ($query) => $query->whereDate('tanggal', '<=', $filters['end_date']),
            )
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        return $this->success(
            OperationalCostResource::collection($costs),
            'Daftar biaya operasional.',
        );
    }

    public function store(StoreOperationalCostRequest $request): JsonResponse
    {
        try {
            $cost = OperationalCost::query()->create($request->validated());

            return $this->success(
                new OperationalCostResource($cost),
                'Biaya operasional berhasil dicatat.',
                201,
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->error(
                'Terjadi kesalahan saat mencatat biaya operasional.',
                500,
                [],
                'SERVER_ERROR',
            );
        }
    }
}
