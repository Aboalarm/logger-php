<?php


namespace Aboalarm\LoggerPhp\Logger;

use Exception;
use Gelf\Transport\HttpTransport;
use Monolog\Formatter\GelfMessageFormatter;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\GelfHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as Monolog;
use Monolog\Processor\WebProcessor;
use Gelf\Publisher;
use Ramsey\Uuid\Uuid;

/**
 * Class Logger
 *
 * @package aboalarm\LoggerPhp\Logger
 */
class Logger
{
    const LOG_TYPE_UNCAUGHT_EXCEPTION = 'uncaught_exception';

    /**
     * @var Monolog
     */
    private $log;

    /**
     * @var string Log path
     */
    private $logPath;

    /**
     * @var int Number of max. log files
     */
    private $logRotationFiles;

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
     * Logger constructor.
     */
    public function __construct()
    {
        // Set minimum log level
        $minLogLevel = false ? Monolog::INFO : Monolog::DEBUG;

        $this->useJobQueue = false;
//        $this->loggerName = config('logging.logger_name', 'aboalarm-web-logger');
//        $this->loggerQueue = config('logging.logger_queue', QueueName::DEFAULT);
//        $this->logPath = config('logging.log_path');
//        $this->logRotationFiles = config('logging.log_rotation_files');
//        $this->graylogHost = config('logging.graylog_host');
//        $this->graylogPort = config('logging.graylog_port');

        $this->log = $this->getMonologInstance();

        // Only log to storage if log path is given
//        if ($this->logPath) {
//            $logPath = storage_path() .'/' . $this->logPath;
//
//            try {
//                $streamHandler = new StreamHandler($logPath, $minLogLevel);
//                $streamHandler->setFormatter(new JsonFormatter());
//                $this->log->pushHandler($streamHandler);
//            } catch (Exception $e) {
//                DatabaseLogger::log('logger.error', self::class . ': Could not push stream handler.');
//            }
//
//            // Only do log rotation if a rotation file number is given
//            if ($this->logRotationFiles) {
//                // Set rotation handler
//                $rotationHandler = new RotatingFileHandler($logPath, 10, $minLogLevel);
//                $rotationHandler->setFormatter(new JsonFormatter());
//                $this->log->pushHandler($rotationHandler);
//            }
//        }

        // Only log to Graylog, if Graylog host is given
//        if ($this->graylogHost) {
            // Set GELF handler
            $gelfPublisher = new Publisher(
                new HttpTransport('log.aboalarm.de', '12202')
            );
            $gelfHandler = new GelfHandler($gelfPublisher, $minLogLevel);
            $gelfHandler->setFormatter(new GelfMessageFormatter());
            $this->log->pushHandler($gelfHandler);
//        }

        // Set processors
        $this->log->pushProcessor(new WebProcessor());
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
    public function debug($message, $context)
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
    public function info($message, $context)
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
    public function notice($message, $context)
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
    public function warning($message, $context)
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
    public function error($message, $context)
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
    public function critical($message, $context)
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
    public function alert($message, $context)
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
    public function emergency($message, $context)
    {
        $this->addRecord(Monolog::EMERGENCY, $message, $context);
    }

    /**
     * Adds a log entry at the CRITICAL level, containing an exception.
     *
     * @param  Exception $ex The exception instance
     * @return void
     */
    public function exception(Exception $ex)
    {
        $trace   = $ex->getTrace();
        $message = $ex->getMessage();

        $this->addRecord(Monolog::CRITICAL, 'Uncaught exception', [
            'log_type'    => static::LOG_TYPE_UNCAUGHT_EXCEPTION,
            'class'   => get_class($ex),
            'message' => $message,
            'file'    => $ex->getFile(),
            'line'    => $ex->getLine(),
            'trace'   => $trace
        ]);
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
        try {
            $context['log_id'] = (string) Uuid::uuid4(); // Add UUID to the context
        } catch (Exception $e) {
            #DatabaseLogger::log('logger.error', 'Failed to set unique log id: ' . $e->getMessage());
        }

        $context['log_microtime'] = microtime(true); // Add request micro time to the context

        if ($this->useJobQueue && !$direct) {
            try {
                $this->dispatchLoggingJob($level, $message, $context, $_SERVER);
            } catch (Exception $e) {
                $this->addRecord($level, $message, $context, true);
            }
        } else {
            try {
                $this->log->addRecord($level, $message, $context);
            } catch (Exception $e) {
                #DatabaseLogger::log('logger.error', 'Failed to write log: ' . $e->getMessage());
            }
        }
    }

    /**
     * Dispatch Logging Job
     *
     * @param int $level
     * @param string $message
     * @param array $context
     * @param array|null  $serverData
     */
    public function dispatchLoggingJob($level, $message, $context, $serverData)
    {
        // Because the server data is lost when the job is dispatched, we pass the data to the Job
        #dispatch((new LoggingJob($level, $message, $context, $serverData))->onQueue($this->loggerQueue));
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
     * @return Monolog
     */
    protected function getMonologInstance()
    {
        return new Monolog($this->loggerName);
    }
}
