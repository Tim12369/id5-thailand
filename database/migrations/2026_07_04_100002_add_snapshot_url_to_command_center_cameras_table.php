<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HTTP Snapshot (a still JPEG served over HTTP by many ONVIF/RTSP-capable
 * recorders) is a distinct, simpler standard from live RTSP -- some cameras
 * expose one without the other. Kept as its own nullable column rather than
 * folded into rtsp_path so a camera can show a snapshot preview even before
 * live streaming is wired up.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('command_center_cameras', function (Blueprint $table) {
            $table->string('snapshot_url', 500)->nullable()->after('rtsp_path');
        });
    }

    public function down(): void
    {
        Schema::table('command_center_cameras', function (Blueprint $table) {
            $table->dropColumn('snapshot_url');
        });
    }
};
