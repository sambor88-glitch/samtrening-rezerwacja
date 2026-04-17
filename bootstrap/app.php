<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'trainer.auth' => \App\Http\Middleware\TrainerAuth::class,
            'client.auth'  => \App\Http\Middleware\ClientAuth::class,
            'admin.auth'   => \App\Http\Middleware\AdminAuth::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Przypomnienia 24h przed treningiem — codziennie o 10:00
        $schedule->command('bookings:reminders')->dailyAt('10:00');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            return response()->json(['error' => 'Nieautoryzowany'], 401);
        });
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            return response()->json(['error' => $e->getMessage(), 'errors' => $e->errors()], 422);
        });
    })
    ->create();
