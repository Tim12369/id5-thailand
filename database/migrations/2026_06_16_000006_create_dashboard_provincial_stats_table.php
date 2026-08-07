<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_provincial_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('dashboard_periods')->cascadeOnDelete();
            $table->string('province_code', 10);
            $table->string('province_name_th', 100);
            $table->string('province_name_en', 100);
            $table->string('region_th', 100)->nullable();
            $table->string('region_en', 100)->nullable();
            $table->integer('total_foreigners')->default(0);
            $table->integer('overstay_count')->default(0);
            $table->integer('tm30_count')->default(0);
            $table->decimal('tm30_compliance_rate', 5, 2)->default(0);
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['period_id', 'province_code']);
            $table->index(['period_id', 'risk_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_provincial_stats');
    }
};
