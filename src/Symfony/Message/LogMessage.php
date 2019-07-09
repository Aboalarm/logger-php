<?php

namespace Aboalarm\LoggerPhp\Symfony\Message;

/**
 * Class LogMessage
 *
 * @package Aboalarm\LoggerPhp\Symfony\Message
 */
class LogMessage
{
    private $level;
    private $message;
    private $context;
    private $serverData;

    public function __construct($level, $message,  array $context = [], array $serverData = [])
    {
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
        $this->serverData = $serverData;
    }

    /**
     * Get Level
     *
     * @return mixed
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Get Message
     *
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get Context
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get ServerData
     *
     * @return array
     */
    public function getServerData(): array
    {
        return $this->serverData;
    }
}
