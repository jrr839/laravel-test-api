<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('authenticated users can retrieve their profile', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/auth/user');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'email_verified_at',
                'created_at',
            ],
        ])
        ->assertJson([
            'data' => [
                'id' => $user->id,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ]);
});

test('unauthenticated users cannot access the user endpoint', function () {
    $response = $this->getJson('/api/auth/user');

    $response->assertUnauthorized();
});

test('user endpoint requires a valid token', function () {
    $response = $this->withHeader('Authorization', 'Bearer invalid-token')
        ->getJson('/api/auth/user');

    $response->assertUnauthorized();
});

test('user profile can be accessed with login token', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    // Login to get a token
    $loginResponse = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $token = $loginResponse->json('data.token');

    // Use the token to access the user profile
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/auth/user');

    $response->assertOk()
        ->assertJson([
            'data' => [
                'id' => $user->id,
                'email' => 'test@example.com',
            ],
        ]);
});

test('user profile returns correct data structure', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/auth/user');

    $response->assertOk();

    $data = $response->json('data');

    expect($data)->toHaveKeys([
        'id',
        'name',
        'email',
        'email_verified_at',
        'created_at',
    ]);

    expect($data['id'])->toBe($user->id);
    expect($data['name'])->toBe($user->name);
    expect($data['email'])->toBe($user->email);
    expect($data['created_at'])->toBeString();
});
