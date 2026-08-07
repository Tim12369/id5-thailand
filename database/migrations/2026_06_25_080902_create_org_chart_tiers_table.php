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
        Schema::create('org_chart_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('org_chart_sections')->cascadeOnDelete();
            $table->string('label_th');
            $table->string('label_en')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_chart_tiers');
    }
};
