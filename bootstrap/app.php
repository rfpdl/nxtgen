<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Laravel Cloud terminates TLS at its load balancer. Without this the app thinks it is on
        // http, and the camera (getUserMedia) refuses to start on the real domain (PoC lesson).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // An expired CSRF token on a long-open phone screen: say so in words, not a 419 page.
        $exceptions->respond(function (Response $response): Response {
            if ($response->getStatusCode() !== 419) {
                return $response;
            }

            return back()->with(
                'error',
                'This screen had been open too long, so that did not go through. Please try again.',
            );
        });
    })->create();
