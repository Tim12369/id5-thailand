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
        Schema::create('command_center_cameras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->nullable()->constrained('stations')->nullOnDelete();
            $table->string('name');
            $table->string('location');
            $table->string('stream_url', 500)->nullable();
            $table->enum('status', ['online', 'offline', 'warning'])->default('offline');
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_center_cameras');
    }
};
