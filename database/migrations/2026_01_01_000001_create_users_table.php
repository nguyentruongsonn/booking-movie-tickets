<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->date('birthday')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('address')->nullable();
            $table->integer('loyalty_points')->default(0);
            $table->tinyInteger('status')->default(1)->comment('0: Banned, 1: Active');
            
            $table->string('provider_id')->nullable();
            $table->string('provider_name')->nullable();
            $table->text('provider_token')->nullable();
            $table->text('provider_refresh_token')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['email', 'status']);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
