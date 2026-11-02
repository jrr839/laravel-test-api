<?php

use App\Http\Resources\Api\AuthResource;
use App\Http\Resources\Api\UserResource;
use Illuminate\Http\Request;

it('wraps the user with a bearer token', function () {
    $user = (object) ['id' => 1];

    $auth = (new AuthResource($user, 'plain-text-token'))->toArray(new Request);

    expect($auth['token'])->toBe('plain-text-token')
        ->and($auth['token_type'])->toBe('Bearer')
        ->and($auth['user'])->toBeInstanceOf(UserResource::class)
        ->and($auth['user']->resource)->toBe($user);
});
