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
        Schema::table('stations', function (Blueprint $table) {
            $table->enum('ops_status', ['normal', 'warning', 'incident'])->default('normal')->after('is_active');
            $table->unsignedSmallInteger('active_officers')->default(0)->after('ops_status');
            $table->unsignedSmallInteger('online_cameras')->default(0)->after('active_officers');
            $table->unsignedSmallInteger('active_incidents')->default(0)->after('online_cameras');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn(['ops_status', 'active_officers', 'online_cameras', 'active_incidents']);
        });
    }
};
