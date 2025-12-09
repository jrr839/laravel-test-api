<?php

use App\Models\User;

it('allows authorized user to access api documentation', function () {
    $user = User::factory()->create(['email' => 'jonathanrr839@gmail.com']);

    $response = $this->actingAs($user)->get('/docs');

    $response->assertOk();
});

it('denies unauthorized users access to api documentation', function () {
    $user = User::factory()->create(['email' => 'other@example.com']);

    $response = $this->actingAs($user)->get('/docs');

    $response->assertForbidden();
    $response->assertSee('Unauthorized access to API documentation.');
});

it('requires authentication to access api documentation', function () {
    $response = $this->get('/docs');

    $response->assertRedirect('/login');
});

it('allows authorized user to access openapi json', function () {
    $user = User::factory()->create(['email' => 'jonathanrr839@gmail.com']);

    $response = $this->actingAs($user)->get('/docs.json');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/json');
});
