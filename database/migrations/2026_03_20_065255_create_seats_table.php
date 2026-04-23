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
            $table->foreignId('screen_id')->constrained('screens')->onDelete('cascade');
            $table->foreignId('seat_type_id')->constrained('seat_types')->onDelete('cascade');
            $table->string('row', 10); // e.g. A, B
            $table->string('number', 10); // e.g. 1, 2
            $table->integer('row_index')->default(0);
            $table->integer('column_index')->default(0);
            $table->string('label', 20)->nullable(); // e.g. A1
            $table->tinyInteger('status')->default(1)->comment('0: Broken, 1: Active');
            $table->timestamps();

            $table->index(['screen_id', 'row', 'number']);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
