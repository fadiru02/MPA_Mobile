<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InvoiceSequence;
use Illuminate\Support\Facades\DB;

final class InvoiceNumberGenerator
{
    /**
     * Generate the next daily invoice number.
     *
     * Must be called inside a database transaction so the row lock is held
     * until the order is persisted.
     */
    public function next(): string
    {
        $now = now();
        $date = $now->toDateString();

        DB::table('invoice_sequences')->insertOrIgnore([
            'tanggal' => $date,
            'last_number' => 0,
        ]);

        $sequence = InvoiceSequence::query()
            ->whereKey($date)
            ->lockForUpdate()
            ->firstOrFail();

        $sequence->last_number++;
        $sequence->save();

        return sprintf('INV-%s-%04d', $now->format('Ymd'), $sequence->last_number);
    }
}
