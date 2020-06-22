<?php

use \Monolog\Logger;

return [
	/**
	 * activate or deactivate the logger globally
	 * can be set in the core .env file
	 */
	'logger_active'        => env('LOGGING_LOGGER_ACTIVE', false),
	'logger_env'           => env('APP_ENV'),
	'logger_name'          => env('LOGGING_LOGGER_NAME'),
	'logger_queue'         => env('LOGGING_LOGGER_QUEUE'),
	'logger_enable_queue'  => env('LOGGING_LOGGER_ENABLE_QUEUE'),
	'logger_min_log_level' => env('LOGGING_LOGGER_MIN_LOG_LEVEL', Logger::INFO),
	'graylog_host'         => env('LOGGING_GRAYLOG_HOST'),
	'graylog_port'         => env('LOGGING_GRAYLOG_PORT'),
];
