<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{

    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->nullable()->unique();
            $table->string('original_title')->nullable();
            $table->decimal('surcharge', 15, 2)->default(0); // Phụ phí phim
            $table->text('description')->nullable();
            $table->integer('duration')->nullable(); // minutes
            $table->date('release_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('age_rating', 20)->nullable();
            $table->tinyInteger('status')->default(1)->comment('0: Private, 1: Showing, 2: Coming Soon, 3: Finished');
            $table->string('director')->nullable();
            $table->string('cast')->nullable();
            $table->string('poster_url')->nullable();
            $table->string('trailer_url')->nullable();
            $table->json('backdrops')->nullable();
            $table->boolean('is_hot')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
