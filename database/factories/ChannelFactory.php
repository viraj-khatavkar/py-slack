<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Channel>
 */
class ChannelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'purpose' => fake()->optional()->sentence(),
            'topic' => fake()->optional()->sentence(),
            'member_count' => fake()->numberBetween(1, 500),
            'created_at_slack' => fake()->dateTimeBetween('-3 years'),
            'message_count' => fake()->numberBetween(0, 10000),
        ];
    }
}
