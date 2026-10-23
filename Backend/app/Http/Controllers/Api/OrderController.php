<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PaymentMethod;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderStateException;
use App\Exceptions\ProductUnavailableException;
use App\Http\Requests\ListOrdersRequest;
use App\Http\Requests\PayOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PublicOrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Throwable;

class OrderController extends ApiController
{
    public function index(ListOrdersRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $orders = Order::query()
            ->with('items')
            ->when(
                isset($filters['status']),
                fn ($query) => $query->where('status_pembayaran', $filters['status']),
            )
            ->when(
                isset($filters['date']),
                fn ($query) => $query->whereDate('created_at', $filters['date']),
            )
            ->when(
                isset($filters['search']),
                fn ($query) => $query->where(function ($query) use ($filters): void {
                    $search = $filters['search'];
                    $query->where('nomor_invoice', 'like', "%{$search}%")
                        ->orWhere('nomor_meja', 'like', "%{$search}%");
                }),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->withQueryString();

        $payload = OrderResource::collection($orders)
            ->toResponse($request)
            ->getData(true);

        return $this->success($payload, 'Daftar pesanan.');
    }

    public function store(StoreOrderRequest $request, OrderService $service): JsonResponse
    {
        try {
            $order = $service->create($request->validated());
            $created = $order->wasRecentlyCreated;

            $resource = $request->user('sanctum') !== null
                ? new OrderResource($order)
                : new PublicOrderResource($order);

            return $this->success(
                $resource,
                $created ? 'Pesanan berhasil dibuat.' : 'Pesanan sudah pernah dibuat.',
                $created ? 201 : 200,
            );
        } catch (InsufficientStockException|ProductUnavailableException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return $this->error(
                'Terjadi kesalahan saat membuat pesanan.',
                500,
                [],
                'SERVER_ERROR',
            );
        }
    }

    public function show(Order $order): JsonResponse
    {
        return $this->success(
            new OrderResource($order->load('items')),
            'Detail pesanan.',
        );
    }

    public function pay(
        PayOrderRequest $request,
        Order $order,
        OrderService $service,
    ): JsonResponse {
        try {
            $validated = $request->validated();
            $method = PaymentMethod::from((string) $validated['metode_pembayaran']);

            $paidOrder = $service->pay($order, $method);

            return $this->success(new OrderResource($paidOrder), 'Pembayaran berhasil.');
        } catch (InvalidOrderStateException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return $this->error(
                'Terjadi kesalahan saat memproses pembayaran.',
                500,
                [],
                'SERVER_ERROR',
            );
        }
    }

    public function cancel(Order $order, OrderService $service): JsonResponse
    {
        try {
            $cancelledOrder = $service->cancel($order);

            return $this->success(new OrderResource($cancelledOrder), 'Pesanan dibatalkan.');
        } catch (InvalidOrderStateException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return $this->error(
                'Terjadi kesalahan saat membatalkan pesanan.',
                500,
                [],
                'SERVER_ERROR',
            );
        }
    }
}
