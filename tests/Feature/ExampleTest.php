<?php

test('guests are redirected to the login page', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('the dashboard returns a successful response for logged in users', function () {
    loginAs();

    $this->get('/')->assertOk()->assertSee('Outreach 30 Hari Terakhir');
});
