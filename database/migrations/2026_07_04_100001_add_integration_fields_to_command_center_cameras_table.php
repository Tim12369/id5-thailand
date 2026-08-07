<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a home for the real network/hardware facts a live RTSP/ONVIF
 * integration needs (see the CCTV Integration Feasibility Assessment) --
 * none of the 15 provincial offices audited have these facts on file yet,
 * so every column here is nullable. Populating them does not, by itself,
 * turn a camera "live" -- `integration_readiness` stays 'not_ready' until
 * a real CameraProviderInterface implementation can act on the data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('command_center_cameras', function (Blueprint $table) {
            $table->string('manufacturer')->nullable()->after('location');
            $table->string('model')->nullable()->after('manufacturer');
            $table->string('cloud_app')->nullable()->after('model');
            $table->string('ip_address', 45)->nullable()->after('cloud_app');
            $table->unsignedInteger('rtsp_port')->nullable()->after('ip_address');
            $table->string('rtsp_path', 500)->nullable()->after('rtsp_port');
            $table->unsignedInteger('onvif_port')->nullable()->after('rtsp_path');
            $table->text('credentials_encrypted')->nullable()->after('onvif_port');
            $table->enum('integration_readiness', ['not_ready', 'pending_review', 'ready'])
                ->default('not_ready')->after('credentials_encrypted');
            $table->text('readiness_notes')->nullable()->after('integration_readiness');
        });
    }

    public function down(): void
    {
        Schema::table('command_center_cameras', function (Blueprint $table) {
            $table->dropColumn([
                'manufacturer', 'model', 'cloud_app', 'ip_address', 'rtsp_port',
                'rtsp_path', 'onvif_port', 'credentials_encrypted',
                'integration_readiness', 'readiness_notes',
            ]);
        });
    }
};
