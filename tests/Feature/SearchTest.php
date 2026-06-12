<?php

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the search page without a query', function () {
    $response = $this->get('/search');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Search/Index')
        ->where('messages', null));
});

it('finds messages via full-text search including stemmed variants', function () {
    Message::factory()->create(['content' => 'We are deploying the portfolio slowly']);
    Message::factory()->create(['content' => 'Nothing to see here']);

    $response = $this->get('/search?q=deploy');

    $response->assertInertia(fn (Assert $page) => $page->where('messages.total', 1));
});

it('does not treat like wildcards as matches', function () {
    Message::factory()->create(['content' => 'completely unrelated text']);

    $response = $this->get('/search?q=%');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page->where('messages.total', 0));
});

it('sorts results by relevance', function () {
    Message::factory()->create(['content' => 'deploy once in a long message about many other things entirely']);
    $best = Message::factory()->create(['content' => 'deploy deploy deploy']);

    $response = $this->get('/search?q=deploy&sort_by=relevance');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('messages.total', 2)
        ->where('messages.data.0.id', $best->id));
});

it('filters search results by channel, author and inclusive date range', function () {
    $channel = Channel::factory()->create();
    $author = User::factory()->create();

    $match = Message::factory()->for($channel)->for($author)->create([
        'content' => 'rebalance day',
        'slack_timestamp' => '2024-05-10 23:30:00',
    ]);
    Message::factory()->for($channel)->create(['content' => 'rebalance day', 'slack_timestamp' => '2024-05-10 10:00:00']);
    Message::factory()->for($author)->create(['content' => 'rebalance day', 'slack_timestamp' => '2024-06-10 10:00:00']);

    $response = $this->get("/search?q=rebalance&channel_id={$channel->id}&user_id={$author->id}&from_date=2024-05-10&to_date=2024-05-10");

    $response->assertInertia(fn (Assert $page) => $page
        ->where('messages.total', 1)
        ->where('messages.data.0.id', $match->id)
        ->where('filterUser.id', $author->id));
});

it('only returns thread parents when sorting by reply count', function () {
    $parent = Message::factory()->create(['content' => 'volatility question']);
    Message::factory()->create(['content' => 'volatility answer', 'parent_id' => $parent->id]);

    $response = $this->get('/search?q=volatility&sort_by=children_count');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('messages.total', 1)
        ->where('messages.data.0.id', $parent->id));
});
