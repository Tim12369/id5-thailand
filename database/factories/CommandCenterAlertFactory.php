<?php

namespace Database\Factories;

use App\Models\CommandCenterAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommandCenterAlert>
 */
class CommandCenterAlertFactory extends Factory
{
    private const TITLES = [
        'system'      => ['Scheduled backup completed', 'Server disk usage above 80%', 'Software update available'],
        'security'    => ['Unauthorized access attempt detected', 'Failed login threshold exceeded', 'Door sensor triggered after hours'],
        'immigration' => ['TM30 submission spike detected', 'Overstay threshold reached for visitor', '90-day report queue backlog'],
        'network'     => ['Network latency degraded', 'VPN tunnel reconnected', 'Internet uplink unstable'],
        'camera'      => ['Camera feed lost', 'Camera tampering suspected', 'Camera back online after outage'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(CommandCenterAlert::TYPES);

        return [
            'station_id' => null,
            'type'       => $type,
            'severity'   => $this->faker->randomElement(['info', 'info', 'warning', 'warning', 'critical']),
            'title'      => $this->faker->randomElement(self::TITLES[$type]),
            'message'    => $this->faker->sentence(12),
            'location'   => null,
            'status'     => $this->faker->randomElement(['new', 'new', 'acknowledged', 'resolved']),
            'created_at' => $this->faker->dateTimeBetween('-2 days', 'now'),
        ];
    }
}
