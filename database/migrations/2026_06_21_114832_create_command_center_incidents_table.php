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
        Schema::create('command_center_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('incident_code', 20)->unique();
            $table->string('type');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->foreignId('station_id')->nullable()->constrained('stations')->nullOnDelete();
            $table->string('location')->nullable();
            $table->text('description');
            $table->enum('status', ['open', 'investigating', 'resolved'])->default('open');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_center_incidents');
    }
};
