<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\Api\AuthResource;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    /**
     * Handle user login request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $user = $request->authenticate();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'data' => new AuthResource($user, $token),
        ], 200);
    }
}
