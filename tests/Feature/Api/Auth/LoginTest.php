<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('users can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                ],
                'token',
                'token_type',
            ],
        ])
        ->assertJson([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => 'test@example.com',
                ],
                'token_type' => 'Bearer',
            ],
        ]);
});

test('login requires an email', function () {
    $response = $this->postJson('/api/auth/login', [
        'password' => 'password123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('login requires a password', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('login requires a valid email format', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'invalid-email',
        'password' => 'password123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('login fails with invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('login fails with non-existent email', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('login token can be used to authenticate', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $token = $response->json('data.token');

    $authResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/user');

    $authResponse->assertOk()
        ->assertJson([
            'email' => 'test@example.com',
        ]);
});

test('login is rate limited after multiple failed attempts', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    // Make 5 failed login attempts
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
    }

    // The 6th attempt should be rate limited
    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    // Verify the error message mentions throttling
    expect($response->json('errors.email.0'))->toContain('Too many');
});
