<?php

namespace Aboalarm\LoggerPhp\Logger;

use Aboalarm\LoggerPhp\Laravel\Jobs\LoggingJob;
use Aboalarm\LoggerPhp\Symfony\Message\LogMessage;
use Aboalarm\LoggerPhp\Logger\Processor\HostnameProcessor;
use Exception;
use Gelf\Transport\HttpTransport;
use Monolog\Formatter\GelfMessageFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\GelfHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger as Monolog;
use Monolog\Processor\WebProcessor;
use Gelf\Publisher;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
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

    const LOG_TYPE_EXCEPTION = 'exception';

    /**
     * @var Monolog
     */
    private $log;
    
    /**
	 * @var boolean
	 */
	private $loggerActive;

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
        $this->loggerActive  = $config['logger_active'];
        $this->useJobQueue = $config['logger_enable_queue'];
        $this->loggerName = $config['logger_name'];
        $this->loggerQueue = $config['logger_queue'];
        $this->graylogHost = $config['graylog_host'];
        $this->graylogPort = (int) $config['graylog_port'];
        $this->env = $config['logger_env'];
        $this->requestHeaders = $headers;
        $this->rid = LoggerHelper::getRequestId($this->requestHeaders);
        $minLogLevel = (int) $config['logger_min_log_level'];

        $this->log = $this->getMonologInstance();

        if ($this->graylogHost && $this->graylogPort) {
            // Set GELF handler
            $gelfPublisher = new Publisher(
                new HttpTransport($this->graylogHost, $this->graylogPort)
            );

            $gelfHandler = new GelfHandler($gelfPublisher, $minLogLevel);
            $gelfHandler->setFormatter(new GelfMessageFormatter());
            $this->log->pushHandler($gelfHandler);
        } else {
            $syslog = new SyslogHandler($this->loggerName);
            $formatter = new LineFormatter("%channel%.%level_name%: %message% %extra%");
            $syslog->setFormatter($formatter);
            $this->log->pushHandler($syslog);
        }

        // Set processors
        $this->setWebProcessor();
        $this->log->pushProcessor(new HostnameProcessor());
    }

    /**
     * Get request id
     *
     * @return string (UUID)
     */
    public function getRequestId()
    {
        return $this->rid;
    }

    public function debug($message, array $context = [], Exception $e = null): ?string
    {
        return $this->addRecord(Monolog::DEBUG, $message, $context, false, $e);
    }

    public function info($message, array $context = [], Exception $e = null): ?string
    {
        return $this->addRecord(Monolog::INFO, $message, $context, false, $e);
    }

    public function notice($message, array $context = [], Exception $e = null): ?string
    {
        return $this->addRecord(Monolog::NOTICE, $message, $context, false, $e);
    }

    public function warning($message, array $context = [], Exception $e = null): ?string
    {
        return $this->addRecord(Monolog::WARNING, $message, $context, false, $e);
    }

    public function error($message, array $context = [], Exception $e = null): ?string
    {
        return $this->addRecord(Monolog::ERROR, $message, $context, false, $e);
    }

    public function critical($message, array $context = [], Exception $e = null): ?string
    {
        return $this->addRecord(Monolog::CRITICAL, $message, $context, false, $e);
    }

    public function alert($message, array $context = [], Exception $e = null): ?string
    {
        return $this->addRecord(Monolog::ALERT, $message, $context, false, $e);
    }

    public function emergency($message, array $context = [], Exception $e = null): ?string
    {
        return $this->addRecord(Monolog::EMERGENCY, $message, $context, false, $e);
    }

    /**
     * @deprecated
     */
    public function exception(Exception $ex, $direct = false, $message = null, $context = [], $logIdentifier = null)
    {
        return $this->addRecord(Monolog::CRITICAL, $message, $context, $direct, $ex);
    }

    public function addRecord(
        int $level,
        string $message,
        array $context = [],
        $direct = false,
        Exception $e = null
    ): ?string {
        if($this->isTestEnv() || !$this->loggerActive) {
            return null;
        }

        if ($e && !isset($context['exception'])) {
            $context['exception'] = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
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

        return $context[LoggerHelper::HEADER_RID];
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
        if (!$this->loggerActive) {
			return;
		}
        
        if($this->isLaravel()) {
            $this->dispatchLaravelLoggingJob($level, $message, $context, $serverData);
        } elseif($this->isSymfony()) {
            $this->dispatchSymfonyLoggingJob($level, $message, $context, $serverData);
        }
    }

    protected function dispatchLaravelLoggingJob(
        int $level,
        string $message,
        array $context = [],
        array $serverData = []
    ) {
        try {
            dispatch(new LoggingJob($level, $message, $context, $serverData, $this))->onQueue($this->loggerQueue);
        } catch (Exception $e) {
            error_log('Failed to dispatch Symfony log: ' . $e->getMessage());
            // On job error log directly
            $this->addRecord($level, 'Failed to dispatch Laravel logging job', $context, true, $e);
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
            error_log('Failed to dispatch Symfony log: ' . $e->getMessage());
            // On job error log directly
            $this->addRecord($level, 'Failed to dispatch Symfony log message', $context, true, $e);
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
    public function log($level, $message, array $context = [])
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
     *
     * @return Logger
     */
    public function setWebProcessor(array $serverData = null)
    {
        $this->log->pushProcessor(new WebProcessor($serverData));

        return $this;
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
        return stripos($this->env, 'test') !== false ? true : false;
    }

    /**
     * For Symfony you can hand over the request stack to get the header params
     *
     * @param RequestStack $requestStack
     *
     * @return Logger
     */
    public function setSymfonyRequestStack(RequestStack $requestStack)
    {
        $this->setRequestHeaders($requestStack->getMasterRequest()->headers->all());
        $this->rid = LoggerHelper::getRequestId($this->requestHeaders);

        return $this;
    }
}
