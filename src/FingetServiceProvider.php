<?php

declare(strict_types=1);

namespace Finget\Laravel;

use Finget\Client;
use Finget\Exception\FingetException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class FingetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/finget.php', 'finget');

        $this->app->singleton(Client::class, function (): Client {
            $key = (string) config('finget.api_key', '');
            if ($key === '') {
                throw new FingetException('FINGET_API_KEY در تنظیمات یا .env خالی است.');
            }

            return new Client(
                apiKey: $key,
                baseUrl: config('finget.base_url') ?: null,
                authUrl: config('finget.auth_url') ?: null,
                timeout: (int) config('finget.timeout', 20),
            );
        });

        $this->app->alias(Client::class, 'finget');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/finget.php' => config_path('finget.php'),
            ], 'finget-config');
        }

        if (config('finget.routes', true)) {
            Route::middleware((array) config('finget.middleware', ['web']))
                ->prefix((string) config('finget.prefix', 'finget'))
                ->group(__DIR__ . '/../routes/web.php');
        }
    }
}
