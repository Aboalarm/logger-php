<?php

namespace Aboalarm\LoggerPhp\Laravel;

use Aboalarm\LoggerPhp\Logger\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

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
            /** @var Request $request */
            $request = app(Request::class);

            $logger = new Logger(
                config('logger_php'),
                Logger::FRAMEWORK_LARAVEL,
                $request->headers->all() ?? [],
            );

            return $logger;
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
