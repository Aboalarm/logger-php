<?php

namespace Aboalarm\LoggerPhp\Laravel;

use Aboalarm\LoggerPhp\Logger\Logger;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class LoggerServiceProvider
 * @package Aboalarm\LoggerPhp\Laravel
 */
class LoggerServiceProvider extends ServiceProvider
{
    const SERVICE_ALIAS = 'Aboalarm.LoggerPhp';
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes(
            [
                __DIR__.'/config/config.php' => config_path('logger_php.php'),
            ],
            'loggerphp'
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(self::SERVICE_ALIAS, function () {
            $requestStack = null;

            return new Logger(config()->get('logger_php'), $requestStack);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [self::SERVICE_ALIAS];
    }
}
