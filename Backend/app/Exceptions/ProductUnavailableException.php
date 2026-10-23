<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductUnavailableException extends Exception
{
    public function __construct(
        public readonly int $productId,
    ) {
        parent::__construct("Produk dengan ID {$productId} tidak tersedia.");
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Produk tidak tersedia.',
            'code' => 'PRODUCT_UNAVAILABLE',
            'errors' => [
                'items' => [
                    "Produk dengan ID {$this->productId} tidak ditemukan atau sedang tidak aktif.",
                ],
            ],
        ], 422);
    }
}
