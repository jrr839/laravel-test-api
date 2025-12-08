<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('authenticated users can logout and revoke current token', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/auth/logout');

    $response->assertNoContent();
});

test('unauthenticated users cannot logout', function () {
    $response = $this->postJson('/api/auth/logout');

    $response->assertUnauthorized();
});

test('after logout token is removed from database', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    // Login to get a token
    $loginResponse = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $token = $loginResponse->json('data.token');

    // User should have 1 token
    expect($user->fresh()->tokens()->count())->toBe(1);

    // Logout using the token
    $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/auth/logout');

    $logoutResponse->assertNoContent();

    // Token should be deleted from database
    expect($user->fresh()->tokens()->count())->toBe(0);
});

test('logout only revokes the current token not all tokens', function () {
    $user = User::factory()->create();

    // Create two tokens
    $token1 = $user->createToken('device-1')->plainTextToken;
    $token2 = $user->createToken('device-2')->plainTextToken;

    // User should have 2 tokens
    expect($user->fresh()->tokens()->count())->toBe(2);

    // Logout from device-1
    $response = $this->withHeader('Authorization', 'Bearer '.$token1)
        ->postJson('/api/auth/logout');

    $response->assertNoContent();

    // User should still have 1 token (device-2)
    expect($user->fresh()->tokens()->count())->toBe(1);

    // Verify the remaining token is device-2
    expect($user->fresh()->tokens()->first()->name)->toBe('device-2');
});

test('authenticated users can logout from all devices', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->deleteJson('/api/auth/logout/all');

    $response->assertNoContent();
});

test('unauthenticated users cannot logout from all devices', function () {
    $response = $this->deleteJson('/api/auth/logout/all');

    $response->assertUnauthorized();
});

test('logout all revokes all user tokens', function () {
    $user = User::factory()->create();

    // Create three tokens
    $token1 = $user->createToken('device-1')->plainTextToken;
    $token2 = $user->createToken('device-2')->plainTextToken;
    $token3 = $user->createToken('device-3')->plainTextToken;

    // User should have 3 tokens
    expect($user->fresh()->tokens()->count())->toBe(3);

    // Logout from all devices using token3
    $response = $this->withHeader('Authorization', 'Bearer '.$token3)
        ->deleteJson('/api/auth/logout/all');

    $response->assertNoContent();

    // All tokens should be deleted from database
    expect($user->fresh()->tokens()->count())->toBe(0);
});

test('user can login again after logout all', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    // Create a token
    $token = $user->createToken('device-1')->plainTextToken;

    // Logout from all devices
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/auth/logout/all')
        ->assertNoContent();

    // Login again
    $loginResponse = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $loginResponse->assertOk()
        ->assertJsonStructure(['data' => ['user', 'token', 'token_type']]);

    // New token should work
    $newToken = $loginResponse->json('data.token');

    $this->withHeader('Authorization', 'Bearer '.$newToken)
        ->getJson('/api/auth/user')
        ->assertOk();
});

test('database verification: logout deletes current token from database', function () {
    $user = User::factory()->create();

    // Create two tokens
    $token1 = $user->createToken('device-1')->plainTextToken;
    $token2 = $user->createToken('device-2')->plainTextToken;

    // User should have 2 tokens
    expect($user->tokens()->count())->toBe(2);

    // Logout from device-1
    $this->withHeader('Authorization', 'Bearer '.$token1)
        ->postJson('/api/auth/logout')
        ->assertNoContent();

    // User should now have 1 token
    expect($user->fresh()->tokens()->count())->toBe(1);
});

test('database verification: logout all deletes all tokens from database', function () {
    $user = User::factory()->create();

    // Create three tokens
    $token1 = $user->createToken('device-1')->plainTextToken;
    $token2 = $user->createToken('device-2')->plainTextToken;
    $token3 = $user->createToken('device-3')->plainTextToken;

    // User should have 3 tokens
    expect($user->tokens()->count())->toBe(3);

    // Logout from all devices
    $this->withHeader('Authorization', 'Bearer '.$token1)
        ->deleteJson('/api/auth/logout/all')
        ->assertNoContent();

    // User should now have 0 tokens
    expect($user->fresh()->tokens()->count())->toBe(0);
});
