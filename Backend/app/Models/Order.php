<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nomor_invoice',
        'nomor_meja',
        'status_pembayaran',
        'metode_pembayaran',
        'total_hpp',
        'total_omset',
        'laba_kotor',
        'client_request_id',
        'catatan',
        'paid_at',
        'cancelled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_pembayaran' => PaymentStatus::class,
            'metode_pembayaran' => PaymentMethod::class,
            'total_hpp' => 'integer',
            'total_omset' => 'integer',
            'laba_kotor' => 'integer',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isPending(): bool
    {
        return $this->status_pembayaran === PaymentStatus::Pending;
    }

    public function isPaid(): bool
    {
        return $this->status_pembayaran === PaymentStatus::Paid;
    }

    public function isCancelled(): bool
    {
        return $this->status_pembayaran === PaymentStatus::Cancelled;
    }
}
