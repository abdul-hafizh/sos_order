<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spk_gambar', function (Blueprint $table) {
            $table->id('id_spk_gambar');
            $table->unsignedBigInteger('id_po');
            $table->string('gambar');
            $table->timestamps();

            $table->foreign('id_po')->references('id_po')->on('t_spk')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spk_gambar');
    }
};
