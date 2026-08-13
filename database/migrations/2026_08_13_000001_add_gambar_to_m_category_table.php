<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('m_category', function (Blueprint $table) {
            $table->string('gambar')->nullable()->after('categoryname');
        });
    }

    public function down(): void
    {
        Schema::table('m_category', function (Blueprint $table) {
            $table->dropColumn('gambar');
        });
    }
};
