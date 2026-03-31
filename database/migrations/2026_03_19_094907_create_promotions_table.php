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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('ma_khuyen_mai')->unique();
            $table->string('ten_khuyen_mai');
            $table->string('loai_khuyen_mai')->nullable();
            $table->string('mo_ta')->nullable();
            $table->enum('loai_giam_gia',['phan_tram','so_tien']);
            $table->decimal('gia_tri_giam',10,2);
            $table->decimal('don_toi_thieu',10,2);
            $table->dateTime('ngay_bat_dau');
            $table->dateTime('ngay_ket_thuc');
            $table->integer('so_lan_su_dung')->default(0);
            $table->integer('so_lan_su_dung_moi_ngay')->default(0);
            $table->boolean('trang_thai')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
