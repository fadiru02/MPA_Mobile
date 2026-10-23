<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderStateException;
use App\Exceptions\ProductUnavailableException;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class OrderService
{
    public function __construct(
        private readonly InvoiceNumberGenerator $invoiceNumbers,
    ) {}

    /**
     * @param  array{nomor_meja?: string|null, catatan?: string|null, client_request_id?: string|null, items: list<array{product_id: int|string, qty: int|string}>}  $data
     */
    public function create(array $data): Order
    {
        $clientRequestId = isset($data['client_request_id'])
            ? (string) $data['client_request_id']
            : null;

        if ($clientRequestId !== null) {
            $existing = $this->findByClientRequestId($clientRequestId);

            if ($existing !== null) {
                return $existing;
            }
        }

        try {
            return DB::transaction(function () use ($data, $clientRequestId): Order {
                if ($clientRequestId !== null) {
                    $existing = $this->findByClientRequestId($clientRequestId);

                    if ($existing !== null) {
                        return $existing;
                    }
                }

                $items = collect($data['items'])
                    ->map(fn (array $item): array => [
                        'product_id' => (int) $item['product_id'],
                        'qty' => (int) $item['qty'],
                    ])
                    ->sortBy('product_id')
                    ->values();

                // Lock in a deterministic order to avoid deadlocks between
                // concurrent orders that contain the same products.
                $products = Product::query()
                    ->whereIn('id', $items->pluck('product_id')->all())
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $lines = [];
                $totalHpp = 0;
                $totalOmset = 0;

                foreach ($items as $item) {
                    $product = $products->get($item['product_id']);

                    if (! $product instanceof Product || ! $product->is_active) {
                        throw new ProductUnavailableException($item['product_id']);
                    }

                    if (! $product->hasStockFor($item['qty'])) {
                        throw new InsufficientStockException(
                            $product->id,
                            $product->nama,
                            $item['qty'],
                            $product->stok,
                        );
                    }

                    $subtotal = $product->harga_jual * $item['qty'];
                    $subtotalHpp = $product->harga_modal * $item['qty'];

                    $lines[] = [
                        'product_id' => $product->id,
                        'nama_produk' => $product->nama,
                        'qty' => $item['qty'],
                        'harga_satuan' => $product->harga_jual,
                        'harga_modal_satuan' => $product->harga_modal,
                        'subtotal' => $subtotal,
                        'subtotal_hpp' => $subtotalHpp,
                    ];

                    $totalOmset += $subtotal;
                    $totalHpp += $subtotalHpp;
                }

                $order = Order::query()->create([
                    'nomor_invoice' => $this->invoiceNumbers->next(),
                    'nomor_meja' => $data['nomor_meja'] ?? null,
                    'status_pembayaran' => PaymentStatus::Pending,
                    'metode_pembayaran' => null,
                    'total_hpp' => $totalHpp,
                    'total_omset' => $totalOmset,
                    'laba_kotor' => $totalOmset - $totalHpp,
                    'client_request_id' => $clientRequestId,
                    'catatan' => $data['catatan'] ?? null,
                ]);

                foreach ($lines as $line) {
                    $order->items()->create($line);

                    $product = $products->get($line['product_id']);

                    if ($product instanceof Product && $product->track_stock) {
                        $product->decrement('stok', $line['qty']);
                    }
                }

                return $order->load('items');
            }, 3);
        } catch (QueryException $exception) {
            // A concurrent request may insert the same client_request_id
            // between the check above and the INSERT. The unique index keeps
            // the data safe; return the winning order instead of a 500.
            if ($clientRequestId !== null && $this->isDuplicateClientRequest($exception)) {
                $existing = $this->findByClientRequestId($clientRequestId);

                if ($existing !== null) {
                    return $existing;
                }
            }

            throw $exception;
        }
    }

    public function pay(Order $order, PaymentMethod $method): Order
    {
        return DB::transaction(function () use ($order, $method): Order {
            $locked = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isPending()) {
                throw InvalidOrderStateException::forPayment($locked);
            }

            $locked->load('items');

            // Recalculate from the frozen item snapshots. Product price
            // changes after checkout must never alter this order.
            $totalHpp = (int) $locked->items->sum('subtotal_hpp');
            $totalOmset = (int) $locked->items->sum('subtotal');

            $locked->forceFill([
                'status_pembayaran' => PaymentStatus::Paid,
                'metode_pembayaran' => $method,
                'total_hpp' => $totalHpp,
                'total_omset' => $totalOmset,
                'laba_kotor' => $totalOmset - $totalHpp,
                'paid_at' => now(),
            ])->save();

            return $locked->load('items');
        }, 3);
    }

    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $locked = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isPending()) {
                throw InvalidOrderStateException::forCancellation($locked);
            }

            $locked->load('items');

            $productIds = $locked->items
                ->pluck('product_id')
                ->unique()
                ->sort()
                ->values()
                ->all();

            // Include soft-deleted products so their stock is still restored.
            $products = Product::withTrashed()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($locked->items as $item) {
                $product = $products->get($item->product_id);

                if ($product instanceof Product && $product->track_stock) {
                    $product->increment('stok', $item->qty);
                }
            }

            $locked->forceFill([
                'status_pembayaran' => PaymentStatus::Cancelled,
                'cancelled_at' => now(),
            ])->save();

            return $locked->load('items');
        }, 3);
    }

    private function findByClientRequestId(string $clientRequestId): ?Order
    {
        return Order::query()
            ->where('client_request_id', $clientRequestId)
            ->with('items')
            ->first();
    }

    private function isDuplicateClientRequest(QueryException $exception): bool
    {
        return $exception->getCode() === '23000'
            && str_contains($exception->getMessage(), 'client_request_id');
    }
}
