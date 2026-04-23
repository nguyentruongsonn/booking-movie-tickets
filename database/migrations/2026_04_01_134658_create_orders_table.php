<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->bigInteger('gateway_order_code')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('showtime_id')->constrained('showtimes')->onDelete('cascade');
            $table->decimal('total_amount', 15, 2);
            $table->json('payload')->nullable();
            
            // Status & Payment
            $table->tinyInteger('status')->default(1)->comment('0: Cancelled, 1: Pending, 2: Paid, 3: Refunded, 4: Expired');
            $table->string('payment_provider', 30)->nullable();
            $table->string('payment_status', 30)->default('created');
            $table->text('checkout_url')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['showtime_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
