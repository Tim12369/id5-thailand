<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_nationality_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('dashboard_periods')->cascadeOnDelete();
            $table->string('nationality_code', 10);
            $table->string('nationality_name_th', 100);
            $table->string('nationality_name_en', 100);
            $table->string('nationality_name_zh', 100)->nullable();
            $table->integer('count')->default(0);
            $table->integer('rank')->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['period_id', 'nationality_code']);
            $table->index(['period_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_nationality_stats');
    }
};
