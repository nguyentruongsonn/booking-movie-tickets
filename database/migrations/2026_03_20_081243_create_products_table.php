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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('ten_san_pham');
            $table->string('loai_san_pham')->nullable();
            $table->decimal('gia_ban', 10, 2);
            $table->integer('so_luong_ton')->default(0);
            $table->string('hinh_anh_url')->nullable();
            $table->string('mo_ta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
