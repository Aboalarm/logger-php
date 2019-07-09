<?php

namespace Aboalarm\LoggerPhp\Logger\Processor;

use Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class RequestIdProcessor
 * @package aboalarm\LoggerPhp\Logger\Processor
 */
class RequestIdProcessor
{
    /**
     * Header name for aboalarm request id
     */
    const HEADER_RID = 'aa-request-id';

    /**
     * @var string Request ID
     */
    private $rid;

    /**
     * @var array RequestHeaders
     */
    private $requestHeaders;

    /**
     * Constructor.
     *
     * @param array Request headers
     */
    public function __construct(array $requestHeaders)
    {
        $this->requestHeaders = $requestHeaders;
    }

    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        if (isset($this->requestHeaders[static::HEADER_RID])) {
            $this->rid = is_array($this->requestHeaders[static::HEADER_RID]) ?
                $this->requestHeaders[static::HEADER_RID][0] :
                $this->requestHeaders[static::HEADER_RID];
        }

        if (!$this->rid) {
            try {
                $uuid4 = Uuid::uuid4();
                $this->rid = $uuid4->toString();
            } catch (Exception $e) {

            }
        }

        $record['extra'][static::HEADER_RID] = $this->rid;

        return $record;
    }
}
