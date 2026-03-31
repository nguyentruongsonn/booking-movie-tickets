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
        Schema::create('showtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained('movies')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->foreignId('format_id')->constrained('formats')->onDelete('cascade');
            $table->foreignId('sound_id')->constrained('sounds')->onDelete('cascade');
            $table->foreignId('subtitle_id')->constrained('subtitles')->onDelete('cascade');
            $table->dateTime('ngay_gio_chieu');
            $table->decimal('gia',10,2);
            $table->enum('trang_thai',['con_ve','het_ve','da_chieu','da_huy'])->default('con_ve');
            $table->timestamps();
            $table->index(['movie_id','room_id','ngay_gio_chieu']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('showtimes');
    }
};
