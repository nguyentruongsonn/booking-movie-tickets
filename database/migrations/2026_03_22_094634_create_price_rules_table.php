<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('price_rules', function (Blueprint $table) {
            $table->id();
            $table->string('ten_quy_tac');
            $table->string('loai_ngay');
            $table->string('khung_gio');
            $table->dateTime('gio_bat_dau');
            $table->dateTime('gio_ket_thuc');
            $table->decimal('he_so_gia',10,2);
            $table->boolean('trang_thai')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_rules');
    }
};
