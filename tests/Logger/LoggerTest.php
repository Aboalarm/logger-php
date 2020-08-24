<?php

use Aboalarm\LoggerPhp\Logger\Logger;
use Aboalarm\LoggerPhp\Logger\LoggerHelper;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class LoggerTest extends TestCase
{
    public function testNewRid()
    {
        $logger = new Logger([],Logger::FRAMEWORK_LARAVEL);
        $this->assertTrue(Uuid::isValid($logger->getRequestId()));
    }

    public function testHeaderRid()
    {
        $rid = '46b7b5ab-4d4f-4d63-85df-d5a69c0911c6';
        $headers = [
            LoggerHelper::HEADER_RID => $rid
        ];
        $logger = new Logger([],Logger::FRAMEWORK_LARAVEL, $headers);
        $this->assertEquals($rid, $logger->getRequestId());
    }
}
