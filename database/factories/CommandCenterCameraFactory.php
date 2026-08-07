<?php

namespace Database\Factories;

use App\Models\CommandCenterCamera;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommandCenterCamera>
 */
class CommandCenterCameraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $locations = ['Main Entrance', 'Service Counter', 'Document Drop-off', 'Parking Lot', 'Back Office', 'Waiting Area'];

        return [
            'station_id' => null,
            'name'       => 'CAM-' . $this->faker->unique()->numerify('##'),
            'location'   => $this->faker->randomElement($locations),
            'stream_url' => null,
            'status'     => $this->faker->randomElement(['online', 'online', 'online', 'offline', 'warning']),
            'last_seen'  => $this->faker->dateTimeBetween('-10 minutes', 'now'),
        ];
    }

    public function offline(): static
    {
        return $this->state(fn () => ['status' => 'offline', 'last_seen' => $this->faker->dateTimeBetween('-2 days', '-1 hour')]);
    }
}
