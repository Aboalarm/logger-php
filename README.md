# PHP Logger aboalarm services


Based on monolog with Graylog.

## Installation

Install the latest version with

```bash
$ composer require aboalarm/logger-php
```

### Laravel

Extend **_config/app.php_**

```php
'providers' => [
    \Aboalarm\LoggerPhp\Laravel\LoggerServiceProvider::class,
],
'aliases' => [
    'Logger' => \Aboalarm\LoggerPhp\Laravel\LoggerFacade::class,
]

```

Add **_config/logger_php.php_**

And copy the content of _src/Laravel/config/config.php_ into _logger_php.php_.

Add new logging vars to the _**.env**_

```ini
LOGGING_LOGGER_NAME=<logger-name>
LOGGING_LOGGER_QUEUE=<logger-queue-name>
LOGGING_GRAYLOG_HOST=<graylog-host>
LOGGING_GRAYLOG_PORT=<graylog-port>

```

## Basic Usage

```php
\Logger::warning('This is my log message.', [
    'info1' => 'foo',
    'info2' => $bar
]);

```

## Log Server

You can find and analyse the log on our log server: https://log.aboalarm.de
Login in 1Password.

## Troubleshooting

### Laravel

#### Verify if the service was detected correctly

    $ php artisan package:discover
    ...
    Discovered Package: aboalarm/logger-php
   
