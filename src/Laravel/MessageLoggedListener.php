<?php

namespace Aboalarm\LoggerPhp\Laravel;

use Illuminate\Log\Events\MessageLogged;

class MessageLoggedListener
{
    public function handle(MessageLogged $event)
    {
        $logger = app('Aboalarm.LoggerPhp');
        $logger->addRecord($event->level, $event->message, $event->context);
    }
}
