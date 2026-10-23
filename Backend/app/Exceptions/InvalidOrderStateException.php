<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvalidOrderStateException extends Exception
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $invoice,
        public readonly PaymentStatus $currentStatus,
        public readonly string $action,
    ) {
        parent::__construct(
            "Pesanan {$invoice} berstatus {$currentStatus->value} dan tidak dapat {$action}.",
        );
    }

    public static function forPayment(Order $order): self
    {
        return new self($order->id, $order->nomor_invoice, $order->status_pembayaran, 'dibayar');
    }

    public static function forCancellation(Order $order): self
    {
        return new self($order->id, $order->nomor_invoice, $order->status_pembayaran, 'dibatalkan');
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code' => 'INVALID_ORDER_STATE',
        ], 409);
    }
}
