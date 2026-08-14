<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('m_ppn', function (Blueprint $table) {
            $table->unique('kode_ppn');
        });
    }

    public function down(): void
    {
        Schema::table('m_ppn', function (Blueprint $table) {
            $table->dropUnique(['kode_ppn']);
        });
    }
};
