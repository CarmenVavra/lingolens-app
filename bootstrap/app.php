<?php

use App\Http\Responses\SessionExpiredResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Shared hosting (e.g. netcup/Plesk) terminates HTTPS in a proxy in front of PHP.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // "Page Expired" (CSRF token mismatch): back to the form with everything that
        // was typed instead of a dead end. Files cannot be restored by the browser.
        // Laravel turns the TokenMismatchException into an HttpException with status 419.
        $exceptions->render(fn (HttpException $e, Request $request) => $e->getStatusCode() === 419
            ? SessionExpiredResponse::make($request)
            : null);
    })->create();
