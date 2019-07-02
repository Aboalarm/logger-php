<?php

namespace Aboalarm\LoggerPhp\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * Class LoggerFacade
 *
 * @package Aboalarm\LoggerPhp\Laravel
 */
class LoggerFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return LoggerServiceProvider::SERVICE_ALIAS;
    }
}
