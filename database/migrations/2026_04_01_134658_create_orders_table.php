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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('ma_don_hang')->unique();
            $table->bigInteger('order_code')->unique();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('suat_chieu_id')->constrained('showtimes')->onDelete('cascade');
            $table->decimal('tong_tien', 15, 2);
            $table->json('payload')->nullable();
            $table->enum('trang_thai', ['pending', 'paid', 'cancelled', 'expired'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
