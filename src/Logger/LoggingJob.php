<?php


namespace Aboalarm\LoggerPhp\Logger;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Aboalarm\LoggerPhp\Logger\Logger;

/**
 * Class LoggingJob
 */
class LoggingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $level;
    private $message;
    private $context;
    private $serverData;

    /**
     * LoggingJob constructor.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @param array $serverData
     */
    public function __construct($level, $message, array $context = [], array $serverData = [])
    {
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
        $this->serverData = $serverData;
    }

    /**
     * Log message
     *
     * @param Logger $logger
     * @return void
     */
    public function handle(Logger $logger)
    {
        $this->delete();
        try {
            $logger->setWebProcessor($this->serverData);
            $logger->addRecord($this->level, $this->message, $this->context, true);
        } catch (Exception $e) {
            error_log(' LoggingJob FAILED ' . $e->getMessage());
        }
    }
}
