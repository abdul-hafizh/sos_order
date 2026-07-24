<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_produk_gambar', function (Blueprint $table) {
            $table->id('id_produk_gambar');
            $table->unsignedBigInteger('id_produk');
            $table->string('nama_file');
            $table->string('path_file');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('id_produk')->references('id_produk')->on('master_produk')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_produk_gambar');
    }
};
