<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('master_produk_detail_gambar', function (Blueprint $table) {
            $table->text('nama_file')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_produk_detail_gambar', function (Blueprint $table) {
            $table->string('nama_file')->change();
        });
    }
};
