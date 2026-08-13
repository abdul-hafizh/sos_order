<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('m_category', 'm_category_2026');
    }

    public function down(): void
    {
        Schema::rename('m_category_2026', 'm_category');
    }
};
