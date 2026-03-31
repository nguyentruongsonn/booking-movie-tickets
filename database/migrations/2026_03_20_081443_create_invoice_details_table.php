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
        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hoa_don_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('san_pham_id')->constrained('products')->onDelete('cascade');
            $table->integer('so_luong')->default(1);
            $table->decimal('don_gia', 10, 2);
            $table->decimal('thanh_tien', 10, 2)->storedAs('so_luong * don_gia');
            $table->timestamps();
            $table->index(['hoa_don_id', 'san_pham_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_details');
    }
};
