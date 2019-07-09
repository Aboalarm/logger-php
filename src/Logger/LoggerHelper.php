<?php


namespace Aboalarm\LoggerPhp\Logger;

use Exception;
use Ramsey\Uuid\Uuid;

/**
 * Class LoggerHelper
 * @package Aboalarm\LoggerPhp\Logger
 */
class LoggerHelper
{
    /**
     * Header name for aboalarm request id
     */
    const HEADER_RID = 'aa-request-id';

    public static function getRequestId($headers)
    {
        if (isset($headers[static::HEADER_RID]) && !empty($headers[static::HEADER_RID])) {
            return is_array($headers[static::HEADER_RID]) ?
                $headers[static::HEADER_RID][0] : $headers[static::HEADER_RID];
        }

        return static::generateRequestId();
    }

    /**
     * Generate request ID (uuid)
     *
     * @return bool|string
     */
    public static function generateRequestId()
    {
        try {
            $uuid4 = Uuid::uuid4();

            return $uuid4->toString();
        } catch (Exception $e) {
            return false;
        }
    }
}
