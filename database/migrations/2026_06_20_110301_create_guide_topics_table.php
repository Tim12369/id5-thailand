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
        Schema::create('guide_topics', function (Blueprint $table) {
            $table->id();
            $table->string('topic', 100)->unique();
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->text('search_keywords')->nullable();
            $table->smallInteger('deadline_days')->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guide_topics');
    }
};
