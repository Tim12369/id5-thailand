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
        Schema::create('command_center_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->nullable()->constrained('stations')->nullOnDelete();
            $table->enum('type', ['system', 'security', 'immigration', 'network', 'camera']);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', ['new', 'acknowledged', 'resolved'])->default('new');
            $table->timestamps();
            $table->index(['status', 'severity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_center_alerts');
    }
};
