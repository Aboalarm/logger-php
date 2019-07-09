<?php

namespace Aboalarm\LoggerPhp\Symfony\MessageHandler;

use Aboalarm\LoggerPhp\Logger\Logger;
use Aboalarm\LoggerPhp\Symfony\Message\LogMessage;
use Exception;
use Monolog\Processor\WebProcessor;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * Class LogMessageHandler
 *
 * @package Aboalarm\LoggerPhp\Logger\MessageHandler
 */
class LogMessageHandler implements MessageHandlerInterface
{
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(LogMessage $message)
    {
        try {
            $this->logger->pushProcessor(new WebProcessor($message->getServerData()));
            $this->logger->addRecord($message->getLevel(), $message->getMessage(), $message->getContext(), true);
        } catch (Exception $e) {
            error_log(' LoggingJob FAILED ' . $e->getMessage());
        }
    }
}
