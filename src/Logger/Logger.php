<?php

namespace Aboalarm\LoggerPhp\Logger;

use Aboalarm\LoggerPhp\Laravel\Jobs\LoggingJob;
use Aboalarm\LoggerPhp\Symfony\Message\LogMessage;
use Aboalarm\LoggerPhp\Logger\Processor\HostnameProcessor;
use Exception;
use Gelf\Transport\HttpTransport;
use Monolog\Formatter\GelfMessageFormatter;
use Monolog\Handler\GelfHandler;
use Monolog\Logger as Monolog;
use Monolog\Processor\WebProcessor;
use Gelf\Publisher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Class Logger
 *
 * @package Aboalarm\LoggerPhp\Logger
 */
class Logger implements LoggerInterface
{
    const FRAMEWORK_LARAVEL = 'laravel';
    const FRAMEWORK_SYMFONY = 'symfony';

    const LOG_TYPE_UNCAUGHT_EXCEPTION = 'uncaught_exception';

    /**
     * @var Monolog
     */
    private $log;

    /**
     * @var string Logger name
     */
    private $loggerName;

    /**
     * @var string Logger queue
     */
    private $loggerQueue;

    /**
     * @var string Graylog host
     */
    private $graylogHost;

    /**
     * @var int Graylog port
     */
    private $graylogPort;

    /**
     * @var bool
     */
    private $useJobQueue;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var array Request headers
     */
    private $requestHeaders = [];

    /**
     * @var string Framework [symfony|laravel]
     */
    private $framework;

    /**
     * @var string uuid
     */
    private $rid;
    
    /**
     * @var string env
     */
    private $env;

    /**
     * Logger constructor.
     *
     * @param array $config
     * @param $framework
     */
    public function __construct(array $config, $framework, $headers = [])
    {
        $this->framework = $framework;
        $this->useJobQueue = $config['logger_enable_queue'];
        $this->loggerName = $config['logger_name'];
        $this->loggerQueue = $config['logger_queue'];
        $this->graylogHost = $config['graylog_host'];
        $this->graylogPort = $config['graylog_port'];
        $this->env = $config['logger_env'];
        $this->requestHeaders = $headers;
        $this->rid = LoggerHelper::getRequestId($this->requestHeaders);
        $minLogLevel = $config['logger_min_log_level'];

        $this->log = $this->getMonologInstance();

        // Set GELF handler
        $gelfPublisher = new Publisher(
            new HttpTransport($this->graylogHost, $this->graylogPort)
        );

        $gelfHandler = new GelfHandler($gelfPublisher, $minLogLevel);
        $gelfHandler->setFormatter(new GelfMessageFormatter());
        $this->log->pushHandler($gelfHandler);

        // Set processors
        $this->setWebProcessor();
        $this->log->pushProcessor(new HostnameProcessor());
    }

    /**
     * Adds a log record at the DEBUG level.
     * E.g. Detailed information on the flow through the system. Expect these to be written to logs only.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return void
     */
    public function debug($message, array $context = [])
    {
        $this->addRecord(Monolog::DEBUG, $message, $context);
    }

    /**
     * Adds a log record at the INFO level.
     *
     * For interesting runtime events (startup/shutdown)
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return void
     */
    public function info($message, array $context = [])
    {
        $this->addRecord(Monolog::INFO, $message, $context);
    }

    /**
     * Adds a log record at the NOTICE level.
     *
     * Normal but significant events
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return void
     */
    public function notice($message, array $context = [])
    {
        $this->addRecord(Monolog::NOTICE, $message, $context);
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * Use of deprecated APIs, poor use of API, 'almost' errors, other runtime situations that are undesirable or
     * unexpected, but not necessarily "wrong". Expect these to be immediately visible on a status console.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return void
     */
    public function warning($message, array $context = [])
    {
        $this->addRecord(Monolog::WARNING, $message, $context);
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * Runtime errors that do not require immediate action but should typically be logged and monitored.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return void
     */
    public function error($message, array $context = [])
    {
        $this->addRecord(Monolog::ERROR, $message, $context);
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * Critical condition. Example: Application component unavailable, unexpected exception.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return void
     */
    public function critical($message, array $context = [])
    {
        $this->addRecord(Monolog::CRITICAL, $message, $context);
    }

    /**
     * Adds a log record at the ALERT level.
     *
     * Action must be taken immediately. Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return void
     */
    public function alert($message, array $context = [])
    {
        $this->addRecord(Monolog::ALERT, $message, $context);
    }

    /**
     * Adds a log record at the EMERGENCY level.
     *
     * System is unusable
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        $this->addRecord(Monolog::EMERGENCY, $message, $context);
    }

    /**
     * Adds a log entry at the CRITICAL level, containing an exception.
     *
     * @param  Exception $ex The exception instance
     * @return void
     */
    public function exception(Exception $ex, $direct = false)
    {
        $trace   = $ex->getTrace();
        $message = $ex->getMessage();

        $this->addRecord(Monolog::CRITICAL, 'Uncaught exception: ' . $ex->getMessage(), [
            'log_type'    => static::LOG_TYPE_UNCAUGHT_EXCEPTION,
            'class'   => get_class($ex),
            'message' => $message,
            'file'    => $ex->getFile(),
            'line'    => $ex->getLine(),
            'trace'   => $trace
        ], $direct);
    }

    /**
     * Overwrite addRecord
     *
     * @param $level
     * @param $message
     * @param array $context
     * @param bool $direct
     */
    public function addRecord($level, $message, array $context = [], $direct = false)
    {
        if($this->isTestEnv()) {
            return;
        }
        
        $context[LoggerHelper::HEADER_RID] = $context[LoggerHelper::HEADER_RID] ?? $this->rid;
        $context['log_microtime'] = microtime(true); // Add request micro time to the context

        if($this->useJobQueue && !$direct) {
            $this->dispatchLoggingJob($level, $message, $context, $_SERVER);
        } else {
            try {
                $this->log->addRecord($level, $message, $context);
            } catch (Exception $e) {
                error_log('Failed to write log: ' . $e->getMessage());
            }
        }
    }

    /**
     * Dispatch logging job
     *
     * @param int $level
     * @param string $message
     * @param array $context
     * @param array $serverData
     */
    protected function dispatchLoggingJob($level, $message, array $context = [], array $serverData = [])
    {
        if($this->isLaravel()) {
            $this->dispatchLaravelLoggingJob($level, $message, $context, $serverData);
        } elseif($this->isSymfony()) {
            $this->dispatchLaravelLoggingJob($level, $message, $context, $serverData);
        }
    }

    /**
     * Dispatch logging job with Laravel
     *
     * @param int $level
     * @param string $message
     * @param array $context
     * @param array $serverData
     */
    protected function dispatchLaravelLoggingJob($level, $message, array $context = [], array $serverData = [])
    {
        try {
            dispatch(new LoggingJob($level, $message, $context, $serverData, $this))->onQueue($this->loggerQueue);
        } catch (Exception $e) {
            // On job error log directly
            $this->exception($e, true);
            $this->addRecord($level, $message, $context, true);
        }
    }

    /**
     * Dispatch logging job with Symfony
     *
     * TODO: Not ready yet!!!
     *
     * @param int $level
     * @param string $message
     * @param array $context
     * @param array $serverData
     */
    protected function dispatchSymfonyLoggingJob($level, $message, array $context = [], array $serverData = [])
    {
        try {
            $this->messageBus->dispatch(new LogMessage($level, $message, $context, $serverData));
        } catch (Exception $e) {
            // On job error log directly
            $this->addRecord($level, $message, $context, true);
        }
    }

    /**
     * @return Monolog
     */
    protected function getMonologInstance()
    {
        return new Monolog($this->loggerName);
    }

    /**
     * Get MessageBus
     *
     * @return MessageBusInterface
     */
    public function getMessageBus(): MessageBusInterface
    {
        return $this->messageBus;
    }

    /**
     * Set MessageBus
     *
     * @param MessageBusInterface $messageBus
     *
     * @return $this
     */
    public function setMessageBus(MessageBusInterface $messageBus = null): Logger
    {
        $this->messageBus = $messageBus;

        return $this;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        $this->addRecord($level, $message, $context = []);
    }

    /**
     * Adds a processor on to the stack.
     *
     * @param  callable $callback
     */
    public function pushProcessor($callback)
    {
        $this->log->pushProcessor($callback);
    }

    /**
     * Get RequestHeaders
     *
     * @return array
     */
    public function getRequestHeaders(): array
    {
        return $this->requestHeaders;
    }

    /**
     * Set RequestHeaders
     *
     * @param array $requestHeaders
     *
     * @return $this
     */
    public function setRequestHeaders(array $requestHeaders): Logger
    {
        $this->requestHeaders = $requestHeaders;

        return $this;
    }

    /**
     * Possibility to pass server data separately
     *
     * @param array $serverData
     */
    public function setWebProcessor(array $serverData = null)
    {

        $this->log->pushProcessor(new WebProcessor($serverData));
    }

    /**
     * @return bool True if Laravel
     */
    public function isLaravel()
    {
        return ($this->framework === static::FRAMEWORK_LARAVEL);
    }

    /**
     * @return bool True if Symfony
     */
    public function isSymfony()
    {
        return ($this->framework === static::FRAMEWORK_SYMFONY);
    }
    
    /**
     * @return bool True if test env
     */
    public function isTestEnv()
    {
        return stripos($this->env, 'testing') !== false ? true : false;
    }
}
