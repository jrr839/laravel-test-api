<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Configure rate limiters for API
        RateLimiter::for('api-login', function ($request) {
            return Limit::perMinute(5)->by($request->input('email').'|'.$request->ip());
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $exception, $request) {
            // Only handle API route exceptions
            if ($request->is('api/*')) {
                // Authentication failures (401)
                if ($exception instanceof AuthenticationException) {
                    return response()->json([
                        'message' => 'Unauthenticated.',
                    ], 401);
                }

                // Validation failures (422)
                if ($exception instanceof ValidationException) {
                    return response()->json([
                        'message' => $exception->getMessage(),
                        'errors' => $exception->errors(),
                    ], 422);
                }

                // Rate limiting (429)
                if ($exception instanceof ThrottleRequestsException) {
                    return response()->json([
                        'message' => 'Too many attempts. Please try again later.',
                    ], 429);
                }

                // Not found (404)
                if ($exception instanceof NotFoundHttpException) {
                    return response()->json([
                        'message' => 'Resource not found.',
                    ], 404);
                }

                // Generic server errors (500)
                if ($exception instanceof \Exception) {
                    $message = config('app.debug')
                        ? $exception->getMessage()
                        : 'An error occurred while processing your request.';

                    return response()->json([
                        'message' => $message,
                    ], 500);
                }
            }

            // Return null to use default exception handling for web routes
            return null;
        });
    })->create();
