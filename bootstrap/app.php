<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        Authenticate::redirectUsing(function (Request $request) {
            return null;
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            if ($exception instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'errors' => $exception->errors(),
                ], $exception->status);
            }

            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Server error. Please try again later.',
            ], 500);
        });
    })
    ->create();
