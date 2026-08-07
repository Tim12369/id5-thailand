<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_periods', function (Blueprint $table) {
            $table->id();
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'yearly'])->default('monthly');
            $table->date('period_date');
            $table->string('label_th', 100)->nullable();
            $table->string('label_en', 100)->nullable();
            $table->boolean('is_latest')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['period_type', 'period_date']);
            $table->index(['period_type', 'is_latest']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_periods');
    }
};
