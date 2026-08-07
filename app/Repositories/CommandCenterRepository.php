<?php

namespace App\Repositories;

use App\Models\CommandCenterAlert;
use App\Models\CommandCenterCamera;
use App\Models\CommandCenterIncident;
use App\Models\Station;
use Illuminate\Database\Eloquent\Collection;

/**
 * Data-access layer for the Command Center. Keeps Eloquent query
 * construction out of the service/controller layers.
 */
class CommandCenterRepository
{
    public function cameras(?int $stationId = null): Collection
    {
        return CommandCenterCamera::with('station')
            ->when($stationId, fn ($q) => $q->forStation($stationId))
            ->orderBy('name')
            ->get();
    }

    public function alerts(?int $stationId = null, int $limit = 20): Collection
    {
        return CommandCenterAlert::with('station')
            ->when($stationId, fn ($q) => $q->forStation($stationId))
            ->newestFirst()
            ->limit($limit)
            ->get();
    }

    public function incidents(?int $stationId = null): Collection
    {
        return CommandCenterIncident::with(['station', 'reportedBy'])
            ->when($stationId, fn ($q) => $q->forStation($stationId))
            ->orderByDesc('created_at')
            ->get();
    }

    public function offices(?int $stationId = null): Collection
    {
        return Station::active()
            ->when($stationId, fn ($q) => $q->where('id', $stationId))
            ->orderBy('name_en')
            ->get();
    }

    public function findIncident(int $id): CommandCenterIncident
    {
        return CommandCenterIncident::findOrFail($id);
    }

    public function countCamerasByStatus(?int $stationId = null): array
    {
        $query = CommandCenterCamera::query()->when($stationId, fn ($q) => $q->forStation($stationId));

        return [
            'online'  => (clone $query)->online()->count(),
            'offline' => (clone $query)->offline()->count(),
        ];
    }

    public function countActiveAlerts(?int $stationId = null): int
    {
        return CommandCenterAlert::query()
            ->when($stationId, fn ($q) => $q->forStation($stationId))
            ->active()
            ->count();
    }

    public function countOpenIncidents(?int $stationId = null): int
    {
        return CommandCenterIncident::query()
            ->when($stationId, fn ($q) => $q->forStation($stationId))
            ->openOrInvestigating()
            ->count();
    }

    public function countOfficesOnline(?int $stationId = null): int
    {
        return Station::active()
            ->when($stationId, fn ($q) => $q->where('id', $stationId))
            ->where('ops_status', '!=', 'incident')
            ->count();
    }

    public function sumActiveOfficers(?int $stationId = null): int
    {
        return (int) Station::active()
            ->when($stationId, fn ($q) => $q->where('id', $stationId))
            ->sum('active_officers');
    }
}
