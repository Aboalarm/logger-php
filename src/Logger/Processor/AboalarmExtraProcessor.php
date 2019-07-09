<?php

namespace Aboalarm\LoggerPhp\Logger\Processor;

/**
 * Class AboalarmExtraProcessor
 *
 * @package aboalarm\LoggerPhp\Logger\Processor
 */
class AboalarmExtraProcessor
{
    /**
     * Extra data to log
     *
     * @var array
     */
    private $extraData = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        if(isset($_SERVER['APP_ENV'])) {
            $this->extraData['env'] = $_SERVER['APP_ENV'];
        }
    }

    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record)
    {

        $record['extra'] = array_merge($record['extra'], $this->extraData);

        return $record;
    }
}
