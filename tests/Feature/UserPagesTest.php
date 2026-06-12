<?php

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lists users by activity by default', function () {
    $quiet = User::factory()->create(['name' => 'Aaron Quiet']);
    $busy = User::factory()->create(['name' => 'Zed Busy']);
    Message::factory()->count(2)->for($busy)->create();

    $response = $this->get('/users');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Users/Index')
        ->where('users.data.0.id', $busy->id)
        ->where('users.data.1.id', $quiet->id)
        ->where('filters.sort', 'active'));
});

it('sorts users by name case-insensitively when requested', function () {
    User::factory()->create(['name' => 'ZULU Caps']);
    User::factory()->create(['name' => 'alpha lower']);

    $response = $this->get('/users?sort=name');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('users.data.0.name', 'alpha lower')
        ->where('users.data.1.name', 'ZULU Caps'));
});

it('excludes bots and searches users by name', function () {
    User::factory()->create(['name' => 'Archive Bot', 'is_bot' => true]);
    $human = User::factory()->create(['name' => 'Archie Human']);

    $response = $this->get('/users?q=Arch');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('users.total', 1)
        ->where('users.data.0.id', $human->id));
});

it('filters a user profile by channel and date range', function () {
    $user = User::factory()->create();
    $channel = Channel::factory()->create();

    $match = Message::factory()->for($user)->for($channel)->create([
        'slack_timestamp' => '2024-05-10 22:00:00',
    ]);
    Message::factory()->for($user)->create(['slack_timestamp' => '2024-05-10 10:00:00']);
    Message::factory()->for($user)->for($channel)->create(['slack_timestamp' => '2023-01-01 10:00:00']);

    $response = $this->get("/users/{$user->id}?channel_id={$channel->id}&from_date=2024-05-10&to_date=2024-05-10");

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Users/Show')
        ->where('messages.total', 1)
        ->where('messages.data.0.id', $match->id));
});
