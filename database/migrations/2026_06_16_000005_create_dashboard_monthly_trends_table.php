<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_monthly_trends', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year');
            $table->tinyInteger('month'); // 1-12
            $table->string('month_label_th', 20); // ม.ค., ก.พ., ...
            $table->string('month_label_en', 20); // Jan, Feb, ...
            $table->integer('total_arrivals')->default(0);
            $table->integer('total_departures')->default(0);
            $table->integer('overstay_count')->default(0);
            $table->integer('tm30_count')->default(0);
            $table->integer('tm30_compliant')->default(0);
            $table->integer('risk_alert_count')->default(0);
            $table->timestamps();

            $table->unique(['year', 'month']);
            $table->index(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_monthly_trends');
    }
};
