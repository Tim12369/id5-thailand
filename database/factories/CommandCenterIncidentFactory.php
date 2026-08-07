<?php

namespace Database\Factories;

use App\Models\CommandCenterIncident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommandCenterIncident>
 */
class CommandCenterIncidentFactory extends Factory
{
    private const TYPES = [
        'Overstay Dispute', 'Suspicious Document', 'Disturbance at Counter',
        'Lost Passport Report', 'Unauthorized Entry Attempt', 'Equipment Malfunction',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $sequence = 0;
        $sequence++;

        return [
            'incident_code' => 'INC-' . now()->format('Ymd') . '-' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
            'type'          => $this->faker->randomElement(self::TYPES),
            'priority'      => $this->faker->randomElement(['low', 'medium', 'medium', 'high', 'critical']),
            'station_id'    => null,
            'location'      => null,
            'description'   => $this->faker->sentence(15),
            'status'        => $this->faker->randomElement(['open', 'open', 'investigating', 'resolved']),
            'reported_by'   => null,
            'created_at'    => $this->faker->dateTimeBetween('-5 days', 'now'),
        ];
    }
}
