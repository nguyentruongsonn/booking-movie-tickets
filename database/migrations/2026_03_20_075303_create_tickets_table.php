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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ma_ve',20)->unique      ();
            $table->foreignId('suat_chieu_id')->constrained('showtimes');
            $table->foreignId('ghe_id')->constrained('seats');
            $table->foreignId('khach_hang_id')->constrained('customers');
            $table->foreignId('hoa_don_id')->constrained('invoices');
            $table->decimal('gia_goc',10,2);
            $table->decimal('gia_ban',10,2);
            $table->enum('trang_thai',['cho_xac_nhan','da_xac_nhan','da_huy','da_su_dung'])->default('da_xac_nhan');
            $table->dateTime('ngay_gio_dat')->useCurrent();
            $table->unique(['ngay_gio_dat', 'ghe_id']);//Không bán 2 lần cùng 1 ghế trong cùng 1 thời điểm
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
