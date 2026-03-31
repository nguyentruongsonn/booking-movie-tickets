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
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->foreignId('seat_type_id')->constrained('seat_types')->onDelete('cascade');
            $table->string('hang_ghe');
            $table->string('so_ghe');
            $table->string('ma',20)->unique();
            $table->boolean('trang_thai')->default(true);
            $table->timestamps();

            $table->index(['room_id','hang_ghe','so_ghe']);
        });
    }

    /**a
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
