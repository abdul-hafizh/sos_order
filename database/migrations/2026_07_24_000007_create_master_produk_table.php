<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_produk', function (Blueprint $table) {
            $table->id('id_produk');
            $table->string('nama_produk');
            $table->text('deskripsi')->nullable();
            $table->string('kode_barang')->nullable()->unique();

            $table->unsignedBigInteger('id_tipe')->nullable();
            $table->unsignedBigInteger('id_satuan')->nullable();
            $table->unsignedBigInteger('id_berat')->nullable();
            $table->unsignedBigInteger('id_ukuran')->nullable();
            $table->unsignedBigInteger('id_warna')->nullable();
            $table->unsignedBigInteger('id_karakter')->nullable();

            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('id_tipe')->references('id_tipe')->on('master_tipe')->nullOnDelete();
            $table->foreign('id_satuan')->references('id_satuan')->on('master_satuan')->nullOnDelete();
            $table->foreign('id_berat')->references('id_berat')->on('master_berat')->nullOnDelete();
            $table->foreign('id_ukuran')->references('id_ukuran')->on('master_ukuran')->nullOnDelete();
            $table->foreign('id_warna')->references('id_warna')->on('master_warna')->nullOnDelete();
            $table->foreign('id_karakter')->references('id_karakter')->on('master_karakter')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_produk');
    }
};
