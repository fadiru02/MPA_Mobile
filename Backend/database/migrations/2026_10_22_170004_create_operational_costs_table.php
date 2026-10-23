<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_costs', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('kategori', 50);
            $table->string('keterangan', 255)->nullable();
            $table->unsignedBigInteger('nominal');
            $table->timestamps();

            $table->index(['tanggal', 'kategori']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_costs');
    }
};
