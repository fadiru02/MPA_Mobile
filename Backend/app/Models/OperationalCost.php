<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OperationalCostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalCost extends Model
{
    /** @use HasFactory<OperationalCostFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tanggal',
        'kategori',
        'keterangan',
        'nominal',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'integer',
        ];
    }
}
