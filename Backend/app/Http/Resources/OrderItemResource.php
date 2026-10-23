<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'nama_produk' => $this->nama_produk,
            'qty' => $this->qty,
            'harga_satuan' => $this->harga_satuan,
            'harga_modal_satuan' => $this->harga_modal_satuan,
            'subtotal' => $this->subtotal,
            'subtotal_hpp' => $this->subtotal_hpp,
        ];
    }
}
