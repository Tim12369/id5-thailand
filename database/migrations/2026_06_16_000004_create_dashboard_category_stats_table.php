<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_category_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('dashboard_periods')->cascadeOnDelete();
            $table->string('category', 50); // tourist, student, worker, investor, overstay, other
            $table->string('label_th', 100);
            $table->string('label_en', 100);
            $table->string('label_zh', 100)->nullable();
            $table->integer('count')->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['period_id', 'category']);
            $table->index(['period_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_category_stats');
    }
};
