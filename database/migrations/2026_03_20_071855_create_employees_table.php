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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('ho_ten',100);
            $table->string('email')->unique();
            $table->string('mat_khau');
            $table->string('so_dien_thoai')->nullable();
            $table->date('ngay_sinh')->nullable();
            $table->enum('gioi_tinh',['Nam','Nữ'])->nullable();
            $table->text('dia_chi')->nullable();
            $table->enum('chuc_vu',['quan_ly','ban_ve','quay_nuoc','soat_ve','admin'])->nullable();
            $table->date('ngay_vao_lam')->nullable();
            $table->boolean('trang_thai')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();

            $table->index(['email','trang_thai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
