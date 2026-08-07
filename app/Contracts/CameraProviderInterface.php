<?php

namespace App\Contracts;

use App\Models\CommandCenterCamera;

/**
 * Abstraction over the camera hardware/streaming backend, so a real
 * provider (ONVIF, RTSP/NVR, Hikvision/Dahua SDK, a VMS API, ...) can be
 * swapped in later without touching the Command Center service or views.
 */
interface CameraProviderInterface
{
    /**
     * Resolve a playable stream URL for this camera, or null if unavailable.
     */
    public function getStreamUrl(CommandCenterCamera $camera): ?string;

    /**
     * Probe the live connection status: 'online', 'offline', or 'warning'.
     */
    public function getStatus(CommandCenterCamera $camera): string;
}
