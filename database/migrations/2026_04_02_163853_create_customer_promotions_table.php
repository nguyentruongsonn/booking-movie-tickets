<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_promotion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('promotion_id')->constrained('promotions')->onDelete('cascade');

            $table->tinyInteger('trang_thai')->default(0);
            $table->timestamp('ngay_su_dung')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->integer('so_lan_da_dung')->default(0);
            $table->timestamps();
            $table->index(['customer_id', 'promotion_id', 'trang_thai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_promotion');
    }
};

