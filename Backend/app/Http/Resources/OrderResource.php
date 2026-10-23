<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomor_invoice' => $this->nomor_invoice,
            'nomor_meja' => $this->nomor_meja,
            'status_pembayaran' => $this->status_pembayaran->value,
            'metode_pembayaran' => $this->metode_pembayaran?->value,
            'total_hpp' => $this->total_hpp,
            'total_omset' => $this->total_omset,
            'laba_kotor' => $this->laba_kotor,
            'catatan' => $this->catatan,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
