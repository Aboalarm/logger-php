<?php

namespace Aboalarm\LoggerPhp\Logger\Processor;


/**
 * Injects value of gethostname in all records
 */
class HostnameProcessor
{
    private static $host;

    public function __construct()
    {
        self::$host = (string) gethostname();
    }

    public function __invoke(array $record): array
    {
        $record['extra']['hostname'] = self::$host;

        return $record;
    }
}
