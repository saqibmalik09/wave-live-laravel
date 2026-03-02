<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Helpers\ApiResponse;
use Illuminate\Auth\AuthenticationException;
// use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'check.maintenance' => \App\Http\Middleware\CheckMaintenance::class,
        ]);
        /*
    |--------------------------------------------------------------------------
    | Force API Authentication to Return JSON (No Redirect)
    |--------------------------------------------------------------------------
    */
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('api/*')) {
                return null; // VERY IMPORTANT → prevents redirect
            }

            return route('login'); // only for web
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
    |--------------------------------------------------------------------------
    | Force API Routes to Always Return JSON
    |--------------------------------------------------------------------------
    */
        $exceptions->shouldRenderJsonWhen(function ($request, $e) {
            return $request->is('api/*');
        });

        /*
    |--------------------------------------------------------------------------
    | Authentication Exception (JWT / auth:api)
    |--------------------------------------------------------------------------
    */
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Unauthenticated', 401);
            }
        });

    //     /*
    // |--------------------------------------------------------------------------
    // | Validation Exception
    // |--------------------------------------------------------------------------
    // */
    //     $exceptions->render(function (ValidationException $e, $request) {
    //         if ($request->is('api/*')) {
    //             return ApiResponse::error(
    //                 'Validation Error',
    //                 422,
    //                 $e->errors()
    //             );
    //         }
    //     });

        /*
    |--------------------------------------------------------------------------
    | HTTP Exceptions (404, 403, 405 etc)
    |--------------------------------------------------------------------------
    */
        $exceptions->render(function (HttpExceptionInterface $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    $e->getMessage() ?: 'HTTP Error',
                    $e->getStatusCode()
                );
            }
        });
    })->create();
