<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use function PHPUnit\Framework\callback;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withSchedule(callback: function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command('report:daily')
            ->everyFiveMinutes()
            ->timezone(timezone: config('app.timezone', 'UTC'))
            ->onSuccess(callback: function (): void {
                info(message: 'Rapor senkronizasyonu başarıyla tamamlandı.');
            })
            ->onFailure(callback: function ():void {
                info(message: 'Rapor senkronizasyonu başarısız oldu.');
            });

        $schedule->command('employee:daily')
            ->dailyAt('05:00')
            ->timezone(config('app.timezone', 'UTC'))
            ->onSuccess(function () {
                info('Personel veri senkronizasyonu başarıyla tamamlandı.');
            })
            ->onFailure(function () {
                info('Personel veri senkronizasyonu başarısız oldu.');
            });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
