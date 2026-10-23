<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProductCategory;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->products() as $product) {
            Product::query()->updateOrCreate(
                ['nama' => $product['nama']],
                $product,
            );
        }
    }

    /**
     * @return list<array{nama: string, kategori: ProductCategory, stok: int, track_stock: bool, harga_modal: int, harga_jual: int, is_active: bool}>
     */
    private function products(): array
    {
        return [
            ['nama' => 'Nasi Kucing', 'kategori' => ProductCategory::Makanan, 'stok' => 100, 'track_stock' => true, 'harga_modal' => 3_000, 'harga_jual' => 5_000, 'is_active' => true],
            ['nama' => 'Nasi Kucing Sambal Teri', 'kategori' => ProductCategory::Makanan, 'stok' => 100, 'track_stock' => true, 'harga_modal' => 3_500, 'harga_jual' => 6_000, 'is_active' => true],
            ['nama' => 'Sate Usus', 'kategori' => ProductCategory::Makanan, 'stok' => 100, 'track_stock' => true, 'harga_modal' => 1_000, 'harga_jual' => 2_500, 'is_active' => true],
            ['nama' => 'Sate Telur Puyuh', 'kategori' => ProductCategory::Makanan, 'stok' => 100, 'track_stock' => true, 'harga_modal' => 2_000, 'harga_jual' => 4_000, 'is_active' => true],
            ['nama' => 'Sate Bakso', 'kategori' => ProductCategory::Makanan, 'stok' => 100, 'track_stock' => true, 'harga_modal' => 2_000, 'harga_jual' => 4_000, 'is_active' => true],
            ['nama' => 'Sate Kulit', 'kategori' => ProductCategory::Makanan, 'stok' => 100, 'track_stock' => true, 'harga_modal' => 1_500, 'harga_jual' => 3_000, 'is_active' => true],
            ['nama' => 'Sate Kepala Ayam', 'kategori' => ProductCategory::Makanan, 'stok' => 50, 'track_stock' => true, 'harga_modal' => 2_500, 'harga_jual' => 5_000, 'is_active' => true],
            ['nama' => 'Wedang Jahe', 'kategori' => ProductCategory::Minuman, 'stok' => 200, 'track_stock' => true, 'harga_modal' => 1_500, 'harga_jual' => 4_000, 'is_active' => true],
            ['nama' => 'Wedang Uwuh', 'kategori' => ProductCategory::Minuman, 'stok' => 200, 'track_stock' => true, 'harga_modal' => 2_000, 'harga_jual' => 5_000, 'is_active' => true],
            ['nama' => 'Teh Panas', 'kategori' => ProductCategory::Minuman, 'stok' => 200, 'track_stock' => true, 'harga_modal' => 800, 'harga_jual' => 3_000, 'is_active' => true],
            ['nama' => 'Jeruk Panas', 'kategori' => ProductCategory::Minuman, 'stok' => 200, 'track_stock' => true, 'harga_modal' => 2_000, 'harga_jual' => 5_000, 'is_active' => true],
            ['nama' => 'Kopi Hitam', 'kategori' => ProductCategory::Minuman, 'stok' => 200, 'track_stock' => true, 'harga_modal' => 1_200, 'harga_jual' => 4_000, 'is_active' => true],
            ['nama' => 'Gorengan', 'kategori' => ProductCategory::Makanan, 'stok' => 100, 'track_stock' => true, 'harga_modal' => 500, 'harga_jual' => 1_500, 'is_active' => true],
            ['nama' => 'Indomie Goreng', 'kategori' => ProductCategory::Makanan, 'stok' => 100, 'track_stock' => true, 'harga_modal' => 3_000, 'harga_jual' => 6_000, 'is_active' => true],
            ['nama' => 'Air Mineral', 'kategori' => ProductCategory::Minuman, 'stok' => 200, 'track_stock' => true, 'harga_modal' => 1_500, 'harga_jual' => 3_000, 'is_active' => true],
        ];
    }
}
