<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Authentication
 *
 * Endpoints for user registration, login, and account management
 */
class UserController extends Controller
{
    /**
     * Get authenticated user
     *
     * Retrieve the currently authenticated user's profile information.
     * Requires a valid Bearer token in the Authorization header.
     *
     * @authenticated
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "email_verified_at": "2025-12-08T10:00:00.000000Z",
     *     "created_at": "2025-12-08T09:00:00.000000Z"
     *   }
     * }
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()),
        ], 200);
    }
}
