<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OperationalCostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tanggal' => $this->tanggal?->toDateString(),
            'kategori' => $this->kategori,
            'keterangan' => $this->keterangan,
            'nominal' => $this->nominal,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
