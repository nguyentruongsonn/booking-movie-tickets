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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('ma_hoa_don', 20)->unique();
            $table->foreignId('khach_hang_id')->constrained('customers');
            $table->foreignId('nhan_vien_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('khuyen_mai_id')->nullable()->constrained('promotions')->nullOnDelete();
            $table->foreignId('suat_chieu_id')->constrained('showtimes');
            $table->dateTime('ngay_lap')->useCurrent();
            $table->decimal('tong_tien_goc', 10, 2);
            $table->decimal('giam_gia', 10, 2)->default(0);
            $table->decimal('tong_tien', 10, 2);
            $table->integer('diem_su_dung')->default(0);
            $table->integer('diem_tich_luy')->default(0);
            $table->enum('phuong_thuc_thanh_toan', ['tien_mat', 'the_tin_dung', 'vi_dien_tu'])->default('vi_dien_tu');
            $table->enum('trang_thai', ['da_thanh_toan', 'cho_thanh_toan', 'da_huy'])->default('cho_thanh_toan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

