<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ita_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('ita_sections')->cascadeOnDelete();
            $table->string('card_number', 20);
            $table->string('title_th');
            $table->string('title_en')->nullable();
            $table->longText('content_th');
            $table->longText('content_en')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('color', 20)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ita_cards');
    }
};
