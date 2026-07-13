<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keranjang_detail', function (Blueprint $table) {
            $table->dropColumn('gambar_permintaan');
        });
    }

    public function down(): void
    {
        Schema::table('keranjang_detail', function (Blueprint $table) {
            $table->string('gambar_permintaan')->nullable();
        });
    }
};