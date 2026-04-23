<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_promotion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('promotion_id')->constrained('promotions')->onDelete('cascade');

            $table->tinyInteger('status')->default(1)->comment('0: Used, 1: Available');
            $table->timestamp('used_at')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'promotion_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_promotion');
    }
};

