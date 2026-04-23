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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('method', 50); // payos, cash, momo
            $table->string('transaction_code')->nullable(); // External ref
            $table->decimal('amount', 15, 2);
            $table->tinyInteger('status')->default(1)->comment('0: Failed, 1: Pending, 2: Completed, 3: Refunded');
            $table->json('payload')->nullable(); // Raw response from provider
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['order_id', 'status']);
            $table->index('transaction_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
