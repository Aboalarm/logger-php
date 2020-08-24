<?php

return [
    'logger_env'           => env('APP_ENV'),
    'logger_name'          => env('LOGGING_LOGGER_NAME'),
    'logger_queue'         => env('LOGGING_LOGGER_QUEUE'),
    'logger_enable_queue'  => env('LOGGING_LOGGER_ENABLE_QUEUE'),
    'logger_min_log_level' => (int) env('LOGGING_LOGGER_MIN_LOG_LEVEL', \Monolog\Logger::INFO),
    'graylog_host'         => env('LOGGING_GRAYLOG_HOST'),
    'graylog_port'         => (int) env('LOGGING_GRAYLOG_PORT')
];
