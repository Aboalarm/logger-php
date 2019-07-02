<?php

return [
    'logger_env'   => env('APP_ENV'),
    'logger_name'  => env('LOGGING_LOGGER_NAME'),
    'logger_queue' => env('LOGGING_LOGGER_QUEUE'),
    'graylog_host' => env('LOGGING_GRAYLOG_HOST'),
    'graylog_port' => env('LOGGING_GRAYLOG_PORT')
];
