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
        $exceptions->renderable(function (Throwable $e) {
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
            return null;
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
