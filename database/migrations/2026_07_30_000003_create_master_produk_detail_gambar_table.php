<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_produk_detail_gambar', function (Blueprint $table) {
            $table->id('id_produk_gambar');
            $table->unsignedBigInteger('id_produk_detail');
            $table->string('nama_file');
            $table->string('path_file');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('id_produk_detail')->references('id_produk_detail')->on('master_produk_detail')->cascadeOnDelete();
        });

        // Pindahkan foto lama (yang terhubung ke master_produk) ke detail yang baru dibuat
        // untuk produk yang sama, supaya foto tidak hilang.
        DB::statement("
            INSERT INTO master_produk_detail_gambar
                (id_produk_detail, nama_file, path_file, created_by, created_at, updated_at)
            SELECT
                d.id_produk_detail, g.nama_file, g.path_file, g.created_by, g.created_at, g.updated_at
            FROM master_produk_gambar g
            INNER JOIN master_produk_detail d ON d.id_produk = g.id_produk
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('master_produk_detail_gambar');
    }
};
