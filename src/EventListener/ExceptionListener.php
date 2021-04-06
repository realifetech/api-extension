<?php

namespace RL\EventListener;

use Psr\Log\LoggerInterface;
use RL\Exception\ApiException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Kernel;

class ExceptionListener
{
    const EXCEPTION_CLASSES = [
        ApiException::class
    ];

    private LoggerInterface $logger;

    private Kernel $kernel;

    public function __construct(LoggerInterface $logger, Kernel $kernel)
    {
        $this->logger = $logger;
        $this->kernel = $kernel;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        $ref = new \ReflectionClass($exception);
        if ($this->shouldBeHandled($exception)) {
            $this->logException($exception);
            $code     = $this->getStatusCode($exception);
            $return   = [
                'code'    => $code,
                'type'    => $ref->getShortName(),
                'message' => $exception->getMessage()
            ];
            $response = JsonResponse::create($return, $code);
            $event->setResponse($response);
        }
    }

    protected function shouldBeHandled($exception)
    {
        foreach (static::EXCEPTION_CLASSES as $exceptionClass) {
            if ($exception instanceof $exceptionClass) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $exception
     * @return int|null
     */
    private function getStatusCode($exception)
    {
        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();

            return (int) $statusCode === 0 ? 400 : $statusCode;
        } else {
            return 500;
        }
    }

    private function logException($exception)
    {
        if ($exception instanceof HttpException) {
            $this->logger->notice($exception);
        } else {
            $this->logger->critical($exception);
        }
    }
}
