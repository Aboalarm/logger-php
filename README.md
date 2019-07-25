# PHP Logger aboalarm services

Based on monolog with Graylog.

General info about logging at aboalarm: https://aboalarm.atlassian.net/wiki/spaces/DEV/pages/61505603/08+Logging

## Installation

Add the repository to the _composer.json_

```json
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
$ composer require aboalarm/logger-php "~0.1"
```
_Verify the current version._

If you install from you host system

```bash
$ composer require aboalarm/logger-php "~0.1" --ignore-platform-reqs --no-scripts
```

Or via composer

```bash
$ docker-compose exec -u www-data app composer require aboalarm/logger-php "~0.1"
```

## .env

```ini
LOGGING_LOGGER_NAME=<logger-name>
LOGGING_LOGGER_QUEUE=<logger-queue-name>
LOGGING_LOGGER_ENABLE_QUEUE=<true|false>
LOGGING_LOGGER_MIN_LOG_LEVEL=100
LOGGING_GRAYLOG_HOST=<graylog-host>
LOGGING_GRAYLOG_PORT=<graylog-port>
```

Each service should have its own logger name:

- aboalarm Core: `LOGGING_LOGGER_NAME=aboalarm-web-logger`
- Provider Service: `LOGGING_LOGGER_NAME=provider-service-logger`
- Banner-Mananger: `LOGGING_LOGGER_NAME=banner-manager-logger`
- Allianz: `LOGGING_LOGGER_NAME=aboalarm-allianz-logger`

Log levels:

- DEBUG: `100`
- INFO: `200`
- NOTICE: `250`
- WARNING: `300`
- ERROR: `400`
- CRITICAL: `500`
- ALERT: `550`
- EMERGENCY: `600`

The package is also looking at `APP_ENV`, which already exists in Laravel & Symfony.

## Framework requirements

### Laravel

Add **_config/logger_php.php_**

And copy the content of _src/Laravel/config/config.php_ into _logger_php.php_.

Add new logging vars to the _**.env**_

After the installation you will have a new Facade `\Logger` which is linking 
to the `\Aboalarm\LoggerPhp\Laravel\LoggerServiceProvider` which returns 
an instance of `\Aboalarm\LoggerPhp\Logger`.


### Symfony

Additional configuration:

See 
 - _Symfony/.env.symfony_
 - _Symfony/config/..._

The redis transport requires php-redis 4.3.0 or higher.
See: https://serverpilot.io/docs/how-to-install-the-php-redis-extension

Consume dispatched messages

```
php bin/console messenger:consume
```

After the installation you will have a new Facade `\Logger` which is linking 
to `\Aboalarm\LoggerPhp\Logger`.


## Basic Usage

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

Special

- exception

## Log Server

You can find and analyse the logs on our log server:

https://log.aboalarm.de

There are several log input streams (https://log.aboalarm.de/system/inputs).

Out log are stored in the "Aboalarm Gelf HTTP" stream:
https://log.aboalarm.de/search?rangetype=relative&fields=message%2Csource&width=1659&highlightMessage=&relative=0&q=gl2_source_input%3A5bcf05962a7d308388eeb4d4

## Troubleshooting

### Laravel

#### Verify if the service was detected correctly

```bash
$ php artisan package:discover
...
Discovered Package: aboalarm/logger-php
...
```
