<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct("Stok {$productName} tidak mencukupi.");
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Stok tidak mencukupi.',
            'code' => 'INSUFFICIENT_STOCK',
            'errors' => [
                'items' => [
                    "Stok {$this->productName} tersisa {$this->available}, diminta {$this->requested}.",
                ],
            ],
        ], 422);
    }
}
