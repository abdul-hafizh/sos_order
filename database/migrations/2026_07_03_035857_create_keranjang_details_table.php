<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keranjang_detail', function (Blueprint $table) {
    $table->id('id_keranjang_detail');

    $table->unsignedBigInteger('id_keranjang');

    $table->integer('id_barang')->nullable();

    $table->string('nama_barang');
    $table->integer('qty')->default(1);
    $table->string('satuan')->nullable();

    $table->enum('tipe_item', ['barang_tersedia', 'barang_baru']);

    $table->string('gambar_permintaan')->nullable();
    $table->text('catatan')->nullable();

    $table->timestamps();

    $table->foreign('id_keranjang')
        ->references('id_keranjang')
        ->on('keranjang')
        ->cascadeOnDelete();

    $table->foreign('id_barang')
        ->references('id_barang')
        ->on('t_barang')
        ->nullOnDelete();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('keranjang_detail');
    }
};