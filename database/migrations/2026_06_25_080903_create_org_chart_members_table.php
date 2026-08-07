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
        Schema::create('org_chart_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tier_id')->constrained('org_chart_tiers')->cascadeOnDelete();
            $table->string('name_th');
            $table->string('name_en')->nullable();
            $table->string('rank_th')->nullable();
            $table->string('position_th')->nullable();
            $table->string('photo_path')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_chart_members');
    }
};
