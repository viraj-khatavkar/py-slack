<?php

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shows top-level channel messages with thread counts', function () {
    $channel = Channel::factory()->create();
    $parent = Message::factory()->for($channel)->create(['slack_timestamp' => '2024-01-10 10:00:00']);
    Message::factory()->for($channel)->create([
        'parent_id' => $parent->id,
        'slack_timestamp' => '2024-01-10 11:00:00',
    ]);

    $response = $this->get("/channels/{$channel->name}");

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Channels/Index')
        ->where('messages.total', 1)
        ->where('messages.data.0.id', $parent->id)
        ->where('messages.data.0.children_count', 1));
});

it('filters messages to a single day', function () {
    $channel = Channel::factory()->create();
    Message::factory()->for($channel)->create(['slack_timestamp' => '2024-01-10 10:00:00']);
    Message::factory()->for($channel)->create(['slack_timestamp' => '2024-02-20 10:00:00']);

    $response = $this->get("/channels/{$channel->name}?date=2024-01-10");

    $response->assertInertia(fn (Assert $page) => $page
        ->where('messages.total', 1)
        ->where('filters.date', '2024-01-10'));
});

it('filters pinned messages', function () {
    $channel = Channel::factory()->create();
    Message::factory()->for($channel)->create(['is_pinned' => true]);
    Message::factory()->for($channel)->create(['is_pinned' => false]);

    $response = $this->get("/channels/{$channel->name}?pinned=1");

    $response->assertInertia(fn (Assert $page) => $page->where('messages.total', 1));
});

it('sorts messages ascending when requested', function () {
    $channel = Channel::factory()->create();
    $older = Message::factory()->for($channel)->create(['slack_timestamp' => '2023-01-01 10:00:00']);
    $newer = Message::factory()->for($channel)->create(['slack_timestamp' => '2024-01-01 10:00:00']);

    $response = $this->get("/channels/{$channel->name}?sort_direction=asc");

    $response->assertInertia(fn (Assert $page) => $page
        ->where('messages.data.0.id', $older->id)
        ->where('messages.data.1.id', $newer->id));
});

it('redirects a jump to date to the page containing that date', function () {
    $channel = Channel::factory()->create();
    $user = User::factory()->create();

    foreach (range(1, 30) as $day) {
        Message::factory()->for($channel)->for($user)->create([
            'slack_timestamp' => sprintf('2024-03-%02d 10:00:00', $day),
        ]);
    }

    $response = $this->get("/channels/{$channel->name}?jump_date=2024-03-01&sort_direction=desc");

    $response->assertRedirect(route('channels.index', [
        'channel' => $channel->name,
        'page' => 2,
        'sort_direction' => 'desc',
        'goto' => '2024-03-01',
    ]));

    $response = $this->get("/channels/{$channel->name}?jump_date=2024-03-01&sort_direction=asc");

    $response->assertRedirect(route('channels.index', [
        'channel' => $channel->name,
        'sort_direction' => 'asc',
        'goto' => '2024-03-01',
    ]));
});

it('keeps jumping within the pinned filter', function () {
    $channel = Channel::factory()->create();
    Message::factory()->for($channel)->count(3)->create(['is_pinned' => false]);

    $response = $this->get("/channels/{$channel->name}?jump_date=2024-03-01&pinned=1");

    $response->assertRedirect(route('channels.index', [
        'channel' => $channel->name,
        'sort_direction' => 'desc',
        'pinned' => 1,
        'goto' => '2024-03-01',
    ]));
});

it('clamps a jump beyond the archive to the last page', function () {
    $channel = Channel::factory()->create();

    foreach (range(1, 30) as $day) {
        Message::factory()->for($channel)->create([
            'slack_timestamp' => sprintf('2024-03-%02d 10:00:00', $day),
        ]);
    }

    $response = $this->get("/channels/{$channel->name}?jump_date=2020-01-01&sort_direction=desc");

    $response->assertRedirect(route('channels.index', [
        'channel' => $channel->name,
        'page' => 2,
        'sort_direction' => 'desc',
        'goto' => '2020-01-01',
    ]));
});

it('passes the jump target date through to the page', function () {
    $channel = Channel::factory()->create();
    Message::factory()->for($channel)->create();

    $response = $this->get("/channels/{$channel->name}?goto=2024-03-05");

    $response->assertInertia(fn (Assert $page) => $page->where('goto', '2024-03-05'));
});

it('redirects a message permalink to the page containing that message', function () {
    $channel = Channel::factory()->create();
    $messages = collect(range(1, 30))->map(fn (int $day) => Message::factory()->for($channel)->create([
        'slack_timestamp' => sprintf('2024-03-%02d 10:00:00', $day),
    ]));

    $target = $messages->first();

    $response = $this->get("/channels/{$channel->name}?jump_msg={$target->id}");

    $response->assertRedirect(route('channels.index', [
        'channel' => $channel->name,
        'page' => 2,
        'sort_direction' => 'desc',
        'goto_msg' => $target->id,
    ]));

    $this->get("/channels/{$channel->name}?page=2&goto_msg={$target->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('gotoMessage', $target->id)
            ->where('messages.data.4.id', $target->id));
});

it('ignores message permalinks that do not belong to the channel', function () {
    $channel = Channel::factory()->create();
    Message::factory()->for($channel)->create();
    $foreign = Message::factory()->create();

    $response = $this->get("/channels/{$channel->name}?jump_msg={$foreign->id}");

    $response->assertRedirect(route('channels.index', ['channel' => $channel->name]));
});
