<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('users can request a password reset link', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);

    $response = $this->postJson('/api/auth/forgot-password', [
        'email' => 'test@example.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'We have emailed your password reset link.',
        ]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('forgot password requires an email', function () {
    $response = $this->postJson('/api/auth/forgot-password', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('forgot password requires a valid email format', function () {
    $response = $this->postJson('/api/auth/forgot-password', [
        'email' => 'invalid-email',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('forgot password returns error for non-existent email', function () {
    $response = $this->postJson('/api/auth/forgot-password', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'message' => "We can't find a user with that email address.",
        ]);
});

test('users can reset their password with valid token', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('old-password'),
    ]);

    $token = Password::createToken($user);

    $response = $this->postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Your password has been reset.',
        ]);

    // Verify password was updated
    $user->refresh();
    expect(Hash::check('new-password', $user->password))->toBeTrue();
});

test('reset password requires a token', function () {
    $response = $this->postJson('/api/auth/reset-password', [
        'email' => 'test@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['token']);
});

test('reset password requires an email', function () {
    $response = $this->postJson('/api/auth/reset-password', [
        'token' => 'some-token',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('reset password requires a password', function () {
    $response = $this->postJson('/api/auth/reset-password', [
        'token' => 'some-token',
        'email' => 'test@example.com',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('reset password requires password confirmation', function () {
    $response = $this->postJson('/api/auth/reset-password', [
        'token' => 'some-token',
        'email' => 'test@example.com',
        'password' => 'new-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('reset password requires matching password confirmation', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);
    $token = Password::createToken($user);

    $response = $this->postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('reset password requires minimum 8 characters', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);
    $token = Password::createToken($user);

    $response = $this->postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('reset password fails with invalid token', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->postJson('/api/auth/reset-password', [
        'token' => 'invalid-token',
        'email' => 'test@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'message' => 'This password reset token is invalid.',
        ]);
});

test('reset password fails with wrong email', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);
    $token = Password::createToken($user);

    $response = $this->postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => 'wrong@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertStatus(400);
});

test('users can login with new password after reset', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('old-password'),
    ]);

    $token = Password::createToken($user);

    // Reset password
    $this->postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    // Login with new password
    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'new-password',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'user',
                'token',
                'token_type',
            ],
        ]);
});

test('password reset token is stored in database', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $this->postJson('/api/auth/forgot-password', [
        'email' => 'test@example.com',
    ]);

    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => 'test@example.com',
    ]);
});

test('password reset token is removed after successful reset', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('old-password'),
    ]);

    $token = Password::createToken($user);

    // Verify token exists
    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => 'test@example.com',
    ]);

    // Reset password
    $this->postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    // Verify token was removed
    $this->assertDatabaseMissing('password_reset_tokens', [
        'email' => 'test@example.com',
    ]);
});
