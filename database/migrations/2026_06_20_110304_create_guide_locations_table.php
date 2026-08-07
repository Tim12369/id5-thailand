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
        Schema::create('guide_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_topic_id')->constrained('guide_topics')->cascadeOnDelete();
            $table->enum('type', ['office', 'online', 'embassy', 'other']);
            $table->string('label_th');
            $table->string('label_en');
            $table->string('label_zh')->nullable();
            $table->string('url', 500)->nullable();
            $table->foreignId('station_id')->nullable()->constrained('stations')->nullOnDelete();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guide_locations');
    }
};
