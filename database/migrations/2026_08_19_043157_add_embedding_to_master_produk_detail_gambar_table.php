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
            $table->json('embedding')->nullable()->after('path_file');
            $table->string('embedding_model', 100)->nullable()->after('embedding');
            $table->text('description')->nullable()->after('embedding_model');
            $table->timestamp('embedding_generated_at')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_produk_detail_gambar', function (Blueprint $table) {
            $table->dropColumn(['embedding', 'embedding_model', 'description', 'embedding_generated_at']);
        });
    }
};
