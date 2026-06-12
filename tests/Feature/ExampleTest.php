<?php

use Inertia\Testing\AssertableInertia as Assert;

test('homepage shows the archive overview', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Home')
        ->has('stats', fn (Assert $stats) => $stats
            ->has('messages')
            ->has('threads')
            ->has('users')
            ->has('channels')
            ->has('oldest')
            ->has('newest'))
        ->has('channelActivity'));
});
