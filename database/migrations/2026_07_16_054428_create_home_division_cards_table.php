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
        Schema::create('home_division_cards', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('title_th');
            $table->string('title_en')->nullable();
            $table->string('desc_th')->nullable();
            $table->string('desc_en')->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_division_cards');
    }
};
