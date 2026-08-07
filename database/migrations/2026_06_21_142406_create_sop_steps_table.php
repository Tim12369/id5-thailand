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
        Schema::create('sop_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sop_id')->constrained('sops')->cascadeOnDelete();
            $table->smallInteger('step_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sop_steps');
    }
};
