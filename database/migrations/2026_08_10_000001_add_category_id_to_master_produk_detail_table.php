<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_produk_detail', function (Blueprint $table) {
            $table->string('category_id', 20)->nullable()->after('id_produk');
        });
    }

    public function down(): void
    {
        Schema::table('master_produk_detail', function (Blueprint $table) {
            $table->dropColumn('category_id');
        });
    }
};
