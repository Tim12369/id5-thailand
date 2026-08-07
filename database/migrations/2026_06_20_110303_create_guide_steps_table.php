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
        Schema::create('guide_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_topic_id')->constrained('guide_topics')->cascadeOnDelete();
            $table->smallInteger('step_number');
            $table->string('title_th');
            $table->string('title_en');
            $table->string('title_zh')->nullable();
            $table->text('description_th')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_zh')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guide_steps');
    }
};
