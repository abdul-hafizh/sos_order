<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_ppn', function (Blueprint $table) {
            $table->increments('id_ppn');
            $table->string('kode_ppn', 20);
            $table->string('nama_ppn', 50);
            $table->decimal('persen_ppn', 5, 2)->default(0);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->smallInteger('active')->default(1);
            $table->string('keterangan', 255)->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('modified_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_ppn');
    }
};
