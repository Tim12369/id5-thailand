<?php

namespace App\Services\CommandCenter;

use App\Contracts\CameraProviderInterface;
use App\Models\CommandCenterIncident;
use App\Models\User;
use App\Repositories\CommandCenterRepository;
use Illuminate\Database\Eloquent\Collection;

class CommandCenterService
{
    public function __construct(
        private CommandCenterRepository $repository,
        private CameraProviderInterface $cameraProvider,
    ) {}

    /**
     * Officers scoped to their own office only see that station's data;
     * everyone else (admin, executive, staff, investigation/suppression/IT)
     * sees the whole network.
     */
    public function scopeFor(User $user): ?int
    {
        return $user->isScopedToOwnStation() ? $user->station_id : null;
    }

    public function cameras(?int $stationId = null): Collection
    {
        return $this->repository->cameras($stationId)->map(function ($camera) {
            $camera->setAttribute('live_status', $this->cameraProvider->getStatus($camera));
            $camera->setAttribute('live_stream_url', $this->cameraProvider->getStreamUrl($camera));
            return $camera;
        });
    }

    public function alerts(?int $stationId = null): Collection
    {
        return $this->repository->alerts($stationId);
    }

    public function incidents(?int $stationId = null): Collection
    {
        return $this->repository->incidents($stationId);
    }

    public function offices(?int $stationId = null): Collection
    {
        return $this->repository->offices($stationId);
    }

    /**
     * Top summary bar: 6 live stat cards.
     */
    public function summary(?int $stationId = null): array
    {
        $cameraCounts = $this->repository->countCamerasByStatus($stationId);
        $now = now();

        return [
            'cameras_active'    => ['value' => $cameraCounts['online'], 'updated_at' => $now->toIso8601String()],
            'cameras_offline'   => ['value' => $cameraCounts['offline'], 'updated_at' => $now->toIso8601String()],
            'active_alerts'     => ['value' => $this->repository->countActiveAlerts($stationId), 'updated_at' => $now->toIso8601String()],
            'offices_online'    => ['value' => $this->repository->countOfficesOnline($stationId), 'updated_at' => $now->toIso8601String()],
            'officers_online'   => ['value' => $this->repository->sumActiveOfficers($stationId), 'updated_at' => $now->toIso8601String()],
            'open_incidents'    => ['value' => $this->repository->countOpenIncidents($stationId), 'updated_at' => $now->toIso8601String()],
        ];
    }

    /**
     * Single combined snapshot used by the 30-second polling endpoint.
     */
    public function snapshot(?int $stationId = null): array
    {
        return [
            'summary'   => $this->summary($stationId),
            'cameras'   => $this->cameras($stationId)->values(),
            'alerts'    => $this->alerts($stationId)->values(),
            'incidents' => $this->incidents($stationId)->values(),
            'offices'   => $this->offices($stationId)->values(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function updateIncidentStatus(CommandCenterIncident $incident, string $status): CommandCenterIncident
    {
        $incident->update(['status' => $status]);

        if ($incident->station) {
            $incident->station->update([
                'active_incidents' => $incident->station->incidents()->openOrInvestigating()->count(),
            ]);
        }

        return $incident->fresh(['station', 'reportedBy']);
    }
}
