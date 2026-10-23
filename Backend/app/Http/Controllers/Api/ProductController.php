<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ProductCategory;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'kategori' => ['nullable', Rule::enum(ProductCategory::class)],
        ]);

        $products = Product::query()
            ->active()
            ->when(
                isset($filters['kategori']),
                fn ($query) => $query->where('kategori', $filters['kategori']),
            )
            ->orderBy('kategori')
            ->orderBy('nama')
            ->get();

        return $this->success(ProductResource::collection($products), 'Daftar menu.');
    }
}
