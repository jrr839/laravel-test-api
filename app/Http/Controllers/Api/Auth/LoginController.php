<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\Api\AuthResource;
use Illuminate\Http\JsonResponse;

/**
 * @group Authentication
 *
 * Endpoints for user registration, login, and account management
 */
class LoginController extends Controller
{
    /**
     * User login
     *
     * Authenticate with email and password to receive an API token.
     * Rate limited to 5 attempts per minute per email+IP combination.
     *
     * @bodyParam email string required User's email address. Example: john@example.com
     * @bodyParam password string required User's password. Example: password123
     *
     * @response 200 {
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com",
     *       "email_verified_at": "2025-12-08T10:00:00.000000Z",
     *       "created_at": "2025-12-08T09:00:00.000000Z"
     *     },
     *     "token": "2|zyxwvutsrqponmlkjihgfedcba987654",
     *     "token_type": "Bearer"
     *   }
     * }
     * @response 401 {
     *   "message": "These credentials do not match our records."
     * }
     * @response 429 {
     *   "message": "Too many login attempts. Please try again in 60 seconds."
     * }
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
