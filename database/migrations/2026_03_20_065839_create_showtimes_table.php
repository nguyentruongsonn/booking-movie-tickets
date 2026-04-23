<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('showtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained('movies')->onDelete('cascade');
            $table->foreignId('screen_id')->constrained('screens')->onDelete('cascade');
            $table->foreignId('format_id')->constrained('formats')->onDelete('cascade');
            $table->foreignId('sound_id')->constrained('sounds')->onDelete('cascade');
            $table->foreignId('subtitle_id')->constrained('subtitles')->onDelete('cascade');
            $table->timestamp('scheduled_at');
            $table->decimal('price', 15, 2);
            $table->tinyInteger('status')->default(1)->comment('0: Cancelled, 1: Available, 2: Sold Out, 3: Finished');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['movie_id', 'screen_id', 'scheduled_at']);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('showtimes');
    }
};
