<?php

use App\Http\Resources\Api\UserResource;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

it('formats a verified user with iso 8601 dates', function () {
    $user = (object) [
        'id' => 1,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'email_verified_at' => CarbonImmutable::parse('2026-01-02 03:04:05', 'UTC'),
        'created_at' => CarbonImmutable::parse('2026-01-01 00:00:00', 'UTC'),
    ];

    expect((new UserResource($user))->toArray(new Request))->toBe([
        'id' => 1,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'email_verified_at' => '2026-01-02T03:04:05+00:00',
        'created_at' => '2026-01-01T00:00:00+00:00',
    ]);
});

it('returns null for an unverified email', function () {
    $user = (object) [
        'id' => 1,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'email_verified_at' => null,
        'created_at' => CarbonImmutable::parse('2026-01-01 00:00:00', 'UTC'),
    ];

    expect((new UserResource($user))->toArray(new Request)['email_verified_at'])->toBeNull();
});

it('does not expose sensitive user attributes', function () {
    $user = (object) [
        'id' => 1,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'email_verified_at' => null,
        'created_at' => CarbonImmutable::parse('2026-01-01 00:00:00', 'UTC'),
        'password' => 'hashed-password',
        'remember_token' => 'remember-token',
        'two_factor_secret' => 'two-factor-secret',
    ];

    expect((new UserResource($user))->toArray(new Request))
        ->not->toHaveKeys(['password', 'remember_token', 'two_factor_secret']);
});
