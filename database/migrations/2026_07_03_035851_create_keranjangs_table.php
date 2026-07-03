<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keranjang', function (Blueprint $table) {

            $table->id('id_keranjang');

            $table->integer('user_id');

            $table->string('status')->default('draft');

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('t_user')
                ->cascadeOnDelete();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keranjang');
    }
};