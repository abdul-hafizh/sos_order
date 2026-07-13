<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keranjang_detail_gambar', function (Blueprint $table) {
            $table->id('id_gambar');
            $table->unsignedBigInteger('id_keranjang_detail');
            $table->string('gambar');
            $table->timestamps();

            $table->foreign('id_keranjang_detail')
                ->references('id_keranjang_detail')
                ->on('keranjang_detail')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keranjang_detail_gambar');
    }
};
