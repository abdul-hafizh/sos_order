<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_spk', function (Blueprint $table) {
            $table->integer('id_ppn')->nullable()->after('id_barang');
            $table->decimal('ppn_persen', 9, 4)->default(0)->after('id_ppn');
            $table->decimal('harga_jual_dpp', 18, 2)->nullable()->after('ppn_persen');
            $table->decimal('nilai_ppn', 18, 2)->default(0)->after('harga_jual_dpp');
            $table->decimal('harga_jual_include_ppn', 18, 2)->nullable()->after('nilai_ppn');
        });
    }

    public function down(): void
    {
        Schema::table('t_spk', function (Blueprint $table) {
            $table->dropColumn([
                'id_ppn',
                'ppn_persen',
                'harga_jual_dpp',
                'nilai_ppn',
                'harga_jual_include_ppn',
            ]);
        });
    }
};
