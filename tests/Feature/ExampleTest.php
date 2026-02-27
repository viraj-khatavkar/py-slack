<?php

test('homepage redirects to momentum channel', function () {
    $response = $this->get('/');

    $response->assertRedirect('/channels/momentum');
});