<?php

namespace App\Services\CommandCenter;

use App\Contracts\CameraProviderInterface;
use App\Models\CommandCenterCamera;

/**
 * Default provider while no physical CCTV/NVR/VMS is connected.
 * Reads the status and stream URL already stored on the camera record,
 * so the rest of the system behaves identically once a real provider
 * (e.g. OnvifCameraProvider, RtspCameraProvider, HikvisionCameraProvider)
 * is bound in place of this one.
 */
class DummyCameraProvider implements CameraProviderInterface
{
    public function getStreamUrl(CommandCenterCamera $camera): ?string
    {
        return $camera->stream_url;
    }

    public function getStatus(CommandCenterCamera $camera): string
    {
        return $camera->status;
    }
}
