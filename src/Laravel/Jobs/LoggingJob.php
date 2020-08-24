<?php

namespace Aboalarm\LoggerPhp\Laravel\Jobs;

use Aboalarm\LoggerPhp\Laravel\LoggerServiceProvider;
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
	 * @var int
	 */
	private $level;
	
	/**
	 * @var string
	 */
	private $message;
	
	/**
	 * @var array
	 */
	private $context = [];
	
	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * LoggingJob constructor.
	 *
	 * @param string      $level
	 * @param string      $message
	 * @param array       $context
	 * @param array       $serverData
	 * @param Logger|null $logger
	 */
	public function __construct($level, $message, array $context = [], array $serverData = [], Logger $logger = null)
	{
		if ($logger) {
			$this->logger = $logger;
		} else {
			$this->logger = app(LoggerServiceProvider::SERVICE_ALIAS);
			$this->logger->setWebProcessor($serverData);
		}

        $this->level = $level;
        $this->message = $message;
        $this->context = $context;

		$this->context['extra']['is_logging_job'] = true;
	}

	/**
	 * Log message
	 *
	 * @return void
	 */
	public function handle()
	{
		$this->delete();


		try {
			$this->logger->addRecord($this->level, $this->message, $this->context, true);
		} catch (Exception $e) {
			error_log(' LoggingJob FAILED ' . $e->getMessage());
		}
	}
}
