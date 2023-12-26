<?php

namespace Elyerr\Passport\Connect;

use Elyerr\Passport\Connect\Console\ClientConsole;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as Provider;

final class ServiceProvider extends Provider implements DeferrableProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->commands([
            ClientConsole::class,
        ]);

        $this->publishes([
            __DIR__ .'/../config/config.php' => config_path('passport_connect.php')
        ],'passport_connect');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'passport_connect');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            ClientConsole::class,
        ];
    }
}
