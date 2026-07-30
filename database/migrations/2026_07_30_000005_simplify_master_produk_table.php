<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_produk', function (Blueprint $table) {
            $table->dropForeign(['id_tipe']);
            $table->dropForeign(['id_satuan']);
            $table->dropForeign(['id_berat']);
            $table->dropForeign(['id_ukuran']);
            $table->dropForeign(['id_warna']);
            $table->dropForeign(['id_karakter']);
            $table->dropUnique(['kode_barang']);
        });

        Schema::table('master_produk', function (Blueprint $table) {
            $table->dropColumn([
                'kode_barang',
                'id_tipe',
                'id_satuan',
                'id_berat',
                'id_ukuran',
                'id_warna',
                'id_karakter',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('master_produk', function (Blueprint $table) {
            $table->string('kode_barang')->nullable()->unique()->after('deskripsi');
            $table->unsignedBigInteger('id_tipe')->nullable()->after('kode_barang');
            $table->unsignedBigInteger('id_satuan')->nullable()->after('id_tipe');
            $table->unsignedBigInteger('id_berat')->nullable()->after('id_satuan');
            $table->unsignedBigInteger('id_ukuran')->nullable()->after('id_berat');
            $table->unsignedBigInteger('id_warna')->nullable()->after('id_ukuran');
            $table->unsignedBigInteger('id_karakter')->nullable()->after('id_warna');

            $table->foreign('id_tipe')->references('id_tipe')->on('master_tipe')->nullOnDelete();
            $table->foreign('id_satuan')->references('id_satuan')->on('master_satuan')->nullOnDelete();
            $table->foreign('id_berat')->references('id_berat')->on('master_berat')->nullOnDelete();
            $table->foreign('id_ukuran')->references('id_ukuran')->on('master_ukuran')->nullOnDelete();
            $table->foreign('id_warna')->references('id_warna')->on('master_warna')->nullOnDelete();
            $table->foreign('id_karakter')->references('id_karakter')->on('master_karakter')->nullOnDelete();
        });
    }
};
