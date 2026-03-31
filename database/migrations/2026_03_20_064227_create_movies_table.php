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
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('ten_phim');
            $table->string('slug')->nullable();
            $table->string('ten_goc')->nullable();
            $table->string('gia')->default(0); //phụ phí theo phim
            $table->text('mo_ta')->nullable();
            $table->integer('thoi_luong')->nullable();
            $table->date('ngay_khoi_chieu')->nullable();
            $table->date('ngay_ket_thuc')->nullable();
            $table->string('do_tuoi')->nullable();
            $table->string('trang_thai')->nullable();
            $table->string('dao_dien')->nullable();
            $table->string('dien_vien')->nullable();
            $table->string('poster_url')->nullable();
            $table->string('trailer_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
