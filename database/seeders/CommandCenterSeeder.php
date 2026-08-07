<?php

namespace Database\Seeders;

use App\Models\CommandCenterAlert;
use App\Models\CommandCenterIncident;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommandCenterSeeder extends Seeder
{
    public function run(): void
    {
        $stations = Station::active()->orderBy('id')->get();

        if ($stations->isEmpty()) {
            $this->command->warn('No stations found — skipping Command Center seed.');
            return;
        }

        $this->seedCameras($stations);
        $this->seedOfficeOperationalStatus($stations);
        $this->seedAlerts($stations);
        $this->seedIncidents($stations);

        $this->command->info('✅ Command Center seeded: cameras, alerts, incidents, office status');
    }

    private function seedCameras($stations): void
    {
        $cameraLocations = ['Main Entrance', 'Service Counter', 'Document Drop-off', 'Parking Lot', 'Back Office', 'Waiting Area'];

        // 6 cameras across the first 3 stations, as requested by the spec.
        $targetStations = $stations->take(3);
        $i = 0;

        foreach ($targetStations as $station) {
            foreach (array_slice($cameraLocations, 0, 2) as $location) {
                $i++;
                $status = match (true) {
                    $i === 5 => 'offline',
                    $i === 6 => 'warning',
                    default  => 'online',
                };

                \App\Models\CommandCenterCamera::factory()->create([
                    'station_id' => $station->id,
                    'name'       => 'CAM-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'location'   => $location . ' — ' . $station->getLocalizedName(),
                    'status'     => $status,
                    'last_seen'  => $status === 'offline' ? now()->subHours(3) : now()->subMinutes(rand(0, 5)),
                ]);
            }
        }
    }

    private function seedOfficeOperationalStatus($stations): void
    {
        foreach ($stations as $index => $station) {
            $opsStatus = match (true) {
                $index === 0 => 'incident',
                $index === 1 || $index === 2 => 'warning',
                default => 'normal',
            };

            $onlineCameras = \App\Models\CommandCenterCamera::where('station_id', $station->id)->online()->count();

            $station->update([
                'ops_status'       => $opsStatus,
                'active_officers'  => rand(2, 9),
                'online_cameras'   => $onlineCameras,
                'active_incidents' => $opsStatus === 'incident' ? rand(1, 2) : 0,
            ]);
        }
    }

    private function seedAlerts($stations): void
    {
        // A handful with explicit, realistic titles + recency for a believable live feed.
        $fixedAlerts = [
            ['type' => 'security',    'severity' => 'critical', 'title' => 'Unauthorized access attempt detected', 'minutes_ago' => 4],
            ['type' => 'camera',      'severity' => 'warning',  'title' => 'Camera feed lost', 'minutes_ago' => 12],
            ['type' => 'immigration', 'severity' => 'warning',  'title' => 'Overstay threshold reached for visitor', 'minutes_ago' => 25],
            ['type' => 'network',     'severity' => 'info',     'title' => 'VPN tunnel reconnected', 'minutes_ago' => 40],
            ['type' => 'system',      'severity' => 'info',     'title' => 'Scheduled backup completed', 'minutes_ago' => 55],
            ['type' => 'security',    'severity' => 'warning',  'title' => 'Failed login threshold exceeded', 'minutes_ago' => 70],
            ['type' => 'immigration', 'severity' => 'critical', 'title' => '90-day report queue backlog', 'minutes_ago' => 90],
            ['type' => 'camera',      'severity' => 'info',     'title' => 'Camera back online after outage', 'minutes_ago' => 120],
        ];

        foreach ($fixedAlerts as $i => $alert) {
            CommandCenterAlert::create([
                'station_id' => $stations[$i % $stations->count()]->id,
                'type'       => $alert['type'],
                'severity'   => $alert['severity'],
                'title'      => $alert['title'],
                'message'    => 'Automated monitoring detected this event during routine operations.',
                'location'   => $stations[$i % $stations->count()]->getLocalizedName(),
                'status'     => $i < 3 ? 'new' : ($i < 6 ? 'acknowledged' : 'resolved'),
                'created_at' => now()->subMinutes($alert['minutes_ago']),
                'updated_at' => now()->subMinutes($alert['minutes_ago']),
            ]);
        }
    }

    private function seedIncidents($stations): void
    {
        $reporter = User::first();

        $fixedIncidents = [
            ['type' => 'Overstay Dispute', 'priority' => 'high', 'status' => 'investigating', 'hours_ago' => 2],
            ['type' => 'Suspicious Document', 'priority' => 'critical', 'status' => 'open', 'hours_ago' => 5],
            ['type' => 'Disturbance at Counter', 'priority' => 'medium', 'status' => 'resolved', 'hours_ago' => 26],
            ['type' => 'Lost Passport Report', 'priority' => 'low', 'status' => 'open', 'hours_ago' => 8],
            ['type' => 'Unauthorized Entry Attempt', 'priority' => 'critical', 'status' => 'investigating', 'hours_ago' => 1],
        ];

        foreach ($fixedIncidents as $i => $incident) {
            $createdAt = now()->subHours($incident['hours_ago']);

            CommandCenterIncident::create([
                'incident_code' => 'INC-' . $createdAt->format('Ymd') . '-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'type'          => $incident['type'],
                'priority'      => $incident['priority'],
                'station_id'    => $stations[$i % $stations->count()]->id,
                'location'      => $stations[$i % $stations->count()]->getLocalizedName(),
                'description'   => "Reported during routine patrol/monitoring — {$incident['type']} requiring officer attention.",
                'status'        => $incident['status'],
                'reported_by'   => $reporter?->id,
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
            ]);
        }
    }
}
