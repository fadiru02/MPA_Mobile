<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 120);
            $table->string('kategori', 50)->index();
            $table->unsignedInteger('stok')->default(0);
            $table->boolean('track_stock')->default(true);
            $table->unsignedInteger('harga_modal');
            $table->unsignedInteger('harga_jual');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'kategori']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
