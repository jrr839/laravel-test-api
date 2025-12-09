<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\Api\AuthResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * @group Authentication
 *
 * Endpoints for user registration, login, and account management
 */
class RegisterController extends Controller
{
    /**
     * Register a new user
     *
     * Create a new user account and receive an API authentication token.
     * Email must be unique and will be converted to lowercase.
     *
     * @bodyParam name string required User's full name. Example: John Doe
     * @bodyParam email string required User's email address. Must be unique. Example: john@example.com
     * @bodyParam password string required Password. Minimum 8 characters. Example: password123
     * @bodyParam password_confirmation string required Password confirmation. Must match password. Example: password123
     *
     * @response 201 {
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com",
     *       "email_verified_at": null,
     *       "created_at": "2025-12-08T10:00:00.000000Z"
     *     },
     *     "token": "1|abcdefghijklmnopqrstuvwxyz123456",
     *     "token_type": "Bearer"
     *   }
     * }
     * @response 422 {
     *   "message": "The email has already been taken.",
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'data' => new AuthResource($user, $token),
        ], 201);
    }
}
