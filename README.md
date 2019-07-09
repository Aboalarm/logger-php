# PHP Logger aboalarm services


Based on monolog with Graylog.

General info about logging at aboalarm: https://aboalarm.atlassian.net/wiki/spaces/DEV/pages/61505603/08+Logging

## Installation

Add the repository to the _composer.json_

```
"repositories": {
    "logger-php": {
        "type": "vcs",
        "url": "https://github.com/Aboalarm/logger-php"
    },
    ...
}
```

Install the latest version with

```bash
$ composer require aboalarm/logger-php @dev
```

If you install from you host system

```bash
$ composer require aboalarm/logger-php @dev --ignore-platform-reqs --no-scripts
```

Or via composer

```bash
$ docker-compose exec -u www-data app composer require aboalarm/logger-php @dev
```

### Laravel

Add **_config/logger_php.php_**

And copy the content of _src/Laravel/config/config.php_ into _logger_php.php_.

Add new logging vars to the _**.env**_

```ini
LOGGING_LOGGER_NAME=<logger-name>
LOGGING_LOGGER_QUEUE=<logger-queue-name>
LOGGING_LOGGER_ENABLE_QUEUE=<true|false>
LOGGING_LOGGER_MIN_LOG_LEVEL=100
LOGGING_GRAYLOG_HOST=<graylog-host>
LOGGING_GRAYLOG_PORT=<graylog-port>

```

## Basic Usage

After the installation you will have a new Facade `\Logger` which is linking 
to the `\Aboalarm\LoggerPhp\Laravel\LoggerServiceProvider`.


```php
\Logger::warning('This is my log message.', [
    'info1' => 'foo',
    'info2' => $bar
]);
```

Available logging methods

- debug
- info
- notice
- warning
- error
- critical
- alert
- emergency

More about the log levels: https://aboalarm.atlassian.net/wiki/spaces/DEV/pages/61505603/08+Logging#id-08Logging-Loglevels

Special

- exception

## Log Server

You can find and analyse the log on our log server: https://log.aboalarm.de
Login in 1Password.

## Troubleshooting

### Laravel

#### Verify if the service was detected correctly

    $ php artisan package:discover
    ...
    Discovered Package: aboalarm/logger-php
    ...
   
