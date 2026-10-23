<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicOrderResource extends JsonResource
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
            'catatan' => $this->catatan,
            'total_omset' => $this->total_omset,
            'items' => PublicOrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
