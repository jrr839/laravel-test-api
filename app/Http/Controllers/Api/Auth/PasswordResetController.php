<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * @group Authentication
 *
 * Endpoints for user registration, login, and account management
 */
class PasswordResetController extends Controller
{
    /**
     * Request password reset
     *
     * Send a password reset link to the user's email address.
     * Check MailHog (http://localhost:8025) in development to retrieve the token.
     *
     * @bodyParam email string required User's email address. Example: john@example.com
     *
     * @response 200 {
     *   "message": "We have emailed your password reset link!"
     * }
     * @response 400 {
     *   "message": "We can't find a user with that email address."
     * }
     */
    public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => __($status),
            ], 200);
        }

        return response()->json([
            'message' => __($status),
        ], 400);
    }

    /**
     * Reset password
     *
     * Reset the user's password using the token from the password reset email.
     * Password must be at least 8 characters and confirmed. Token expires after 60 minutes.
     *
     * @bodyParam token string required Password reset token from email. Example: abc123token456
     * @bodyParam email string required User's email address. Example: john@example.com
     * @bodyParam password string required New password. Minimum 8 characters. Example: newpassword123
     * @bodyParam password_confirmation string required Password confirmation. Must match password. Example: newpassword123
     *
     * @response 200 {
     *   "message": "Your password has been reset!"
     * }
     * @response 400 {
     *   "message": "This password reset token is invalid."
     * }
     * @response 422 {
     *   "message": "The password confirmation does not match.",
     *   "errors": {
     *     "password": ["The password confirmation does not match."]
     *   }
     * }
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __($status),
            ], 200);
        }

        return response()->json([
            'message' => __($status),
        ], 400);
    }
}
