<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Authentication
 *
 * Endpoints for user registration, login, and account management
 */
class LogoutController extends Controller
{
    /**
     * Logout (current device)
     *
     * Revoke the current API token used for authentication.
     * Other tokens for this user remain valid.
     *
     * @authenticated
     *
     * @response 204 scenario="success" {}
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    /**
     * Logout (all devices)
     *
     * Revoke all API tokens for the authenticated user.
     * This will log the user out from all devices.
     *
     * @authenticated
     *
     * @response 204 scenario="success" {}
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(null, 204);
    }
}
