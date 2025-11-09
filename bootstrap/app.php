<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(App\Http\Middleware\ForceJson::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (Throwable $e, $request) {
            if ($e instanceof App\Exceptions\ApiException) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'error' => [
                        'message' => $e->getMessage(),
                        'code' => $e->codeStr,
                        'errors' => $e->errors,
                    ],
                    'meta' => null,
                ], $e->status);
            }

            // Map authentication exceptions to a standardized 401 response
            if ($e instanceof Illuminate\Auth\AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'error' => [
                        'message' => $e->getMessage() ?: 'Unauthenticated',
                        'code' => 'UNAUTHENTICATED',
                        'errors' => null,
                    ],
                    'meta' => null,
                ], 401);
            }

            // Hide unhandled errors by default; only reveal details when DEV_REPORT=true
            $allowTrace = filter_var(env('DEV_REPORT', false), FILTER_VALIDATE_BOOLEAN) === true;

            $payload = [
                'success' => false,
                'data' => null,
                'error' => [
                    'message' => $allowTrace ? ($e->getMessage() ?: 'Invalid error') : 'Invalid error',
                    'code' => 'INTERNAL_ERROR',
                    'errors' => null,
                ],
                'meta' => $allowTrace ? [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ];

            return response()->json($payload, 500);
        });
        $exceptions->renderable(function (App\Exceptions\DomainValidationException $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => 'DOMAIN_VALIDATION',
                    'errors' => null,
                ],
                'meta' => null,
            ], 404);
        });
    })->create();
