<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('dashboard_periods')->cascadeOnDelete();
            $table->string('metric_key', 100);
            $table->decimal('value', 15, 2)->default(0);
            $table->decimal('previous_value', 15, 2)->nullable();
            $table->string('unit', 50)->nullable(); // count, percent, etc.
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['period_id', 'metric_key']);
            $table->index('metric_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_kpis');
    }
};
