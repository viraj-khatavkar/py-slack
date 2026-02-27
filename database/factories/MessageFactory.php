<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $timestamp = fake()->dateTimeBetween('-1 year');

        return [
            'user_id' => User::factory(),
            'channel_id' => Channel::factory(),
            'content' => fake()->paragraph(),
            'ts' => (string) $timestamp->getTimestamp().'.'.fake()->randomNumber(6),
            'slack_timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'reactions' => null,
            'is_edited' => false,
            'has_files' => false,
            'reply_users' => null,
            'reply_users_count' => 0,
            'is_pinned' => false,
        ];
    }
}
