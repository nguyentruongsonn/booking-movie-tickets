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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('ho_ten', 100);
            $table->string('email')->unique();
            $table->string('mat_khau')->nullable();
            $table->string('so_dien_thoai')->nullable();
            $table->date('ngay_sinh')->nullable();
            $table->enum('gioi_tinh', ['Nam', 'Nữ'])->nullable();
            $table->integer('diem_tich_luy')->default(0);
            $table->boolean('trang_thai')->default(true);
            $table->string('provider_id')->nullable();
            $table->string('provider_name')->nullable();
            $table->text('provider_token')->nullable();
            $table->text('provider_refresh_token')->nullable();
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
        Schema::dropIfExists('customers');
    }
};
