<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_invoice', 30)->unique();
            $table->string('nomor_meja', 10)->nullable()->index();
            $table->string('status_pembayaran', 20)->default('pending');
            $table->string('metode_pembayaran', 20)->nullable();
            $table->unsignedBigInteger('total_hpp')->default(0);
            $table->unsignedBigInteger('total_omset')->default(0);
            $table->bigInteger('laba_kotor')->default(0);
            $table->string('client_request_id', 36)->nullable()->unique();
            $table->string('catatan', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status_pembayaran', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
