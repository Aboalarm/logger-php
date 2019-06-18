<?php

namespace Aboalarm\LoggerPhp\Logger\Processors;

use Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class RequestIdProcessor
 * @package aboalarm\LoggerPhp\Logger\Processors
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
     * @var RequestStack
     */
    private $requestStack;

    /**
     * Constructor.
     *
     * @param RequestStack $stack The request stack
     */
    public function __construct(RequestStack $stack)
    {
        $this->requestStack = $stack;
    }

    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $request = $this->requestStack->getMasterRequest();

        if ($request && !$this->rid) {
            $this->rid = $request->headers->get(static::HEADER_RID);
        } else {
            try {
                $uuid4 = Uuid::uuid4();
                $this->rid = $uuid4->toString();
            } catch (Exception $e) {

            }
        }

        $record['extra']['aa-request-id'] = $this->rid;

        return $record;
    }
}