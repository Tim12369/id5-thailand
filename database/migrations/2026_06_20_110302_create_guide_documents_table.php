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
        Schema::create('guide_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_topic_id')->constrained('guide_topics')->cascadeOnDelete();
            $table->foreignId('form_id')->nullable()->constrained('public_forms')->nullOnDelete();
            $table->string('name_th');
            $table->string('name_en');
            $table->string('name_zh')->nullable();
            $table->boolean('is_required')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guide_documents');
    }
};
