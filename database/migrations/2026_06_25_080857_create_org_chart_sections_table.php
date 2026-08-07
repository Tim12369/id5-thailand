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
        Schema::create('org_chart_sections', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name_th');
            $table->string('name_en')->nullable();
            $table->string('theme', 30)->default('navy');
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_chart_sections');
    }
};
