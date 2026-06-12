<?php

use App\Models\Message;
use App\Models\User;

it('returns a single message with user, channel and reply count', function () {
    $parent = Message::factory()->create();
    Message::factory()->count(2)->create(['parent_id' => $parent->id]);

    $response = $this->getJson("/api/messages/{$parent->id}");

    $response->assertSuccessful();
    $response->assertJsonPath('id', $parent->id);
    $response->assertJsonPath('children_count', 2);
    expect($response->json('content_html'))->toBeString()->not->toBeEmpty();
    expect($response->json('user.id'))->toBe($parent->user_id);
    expect($response->json('channel.id'))->toBe($parent->channel_id);
});

it('returns thread replies in chronological order', function () {
    $parent = Message::factory()->create();
    $second = Message::factory()->create(['parent_id' => $parent->id, 'slack_timestamp' => '2024-01-02 10:00:00']);
    $first = Message::factory()->create(['parent_id' => $parent->id, 'slack_timestamp' => '2024-01-01 10:00:00']);

    $response = $this->getJson("/api/messages/{$parent->id}/children");

    $response->assertSuccessful();
    $response->assertJsonPath('total', 2);
    $response->assertJsonPath('data.0.id', $first->id);
    $response->assertJsonPath('data.1.id', $second->id);
});

it('searches users for the author filter ordered by activity', function () {
    User::factory()->create(['name' => 'Anita Bot', 'is_bot' => true]);
    $active = User::factory()->create(['name' => 'Anita Active']);
    $quiet = User::factory()->create(['name' => 'Anita Quiet']);
    Message::factory()->count(2)->for($active)->create();

    $response = $this->getJson('/api/users/search?q=Anita');

    $response->assertSuccessful();
    expect($response->json())->toHaveCount(2);
    $response->assertJsonPath('0.id', $active->id);
    $response->assertJsonPath('1.id', $quiet->id);
});
