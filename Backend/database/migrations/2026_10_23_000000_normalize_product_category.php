<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Normalize categories from the previous enum so existing rows remain
        // readable by the new ProductCategory cast.
        DB::table('products')
            ->where('kategori', 'wedangan')
            ->update(['kategori' => 'minuman']);

        DB::table('products')
            ->whereIn('kategori', ['nasi_kucing', 'sate', 'lainnya'])
            ->update(['kategori' => 'makanan']);

        // The seeded mineral water was previously stored as "lainnya".
        DB::table('products')
            ->whereIn('nama', ['Air Mineral'])
            ->update(['kategori' => 'minuman']);
    }

    public function down(): void
    {
        // Irreversible: the original categories cannot be inferred reliably.
    }
};
