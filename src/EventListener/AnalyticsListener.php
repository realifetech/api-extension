<?php

namespace RL\EventListener;

use Doctrine\ORM\NonUniqueResultException;
use RL\Repository\AnalyticsRepository;
use RL\Repository\ApiKeyRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class AnalyticsListener
{
    /** @var ApiKeyRepository */
    private ApiKeyRepository $apiKeyRepository;

    /** @var AnalyticsRepository */
    private AnalyticsRepository $analyticsRepository;

    public function __construct(
        ApiKeyRepository $apiKeyRepository,
        AnalyticsRepository $analyticsRepository
    ) {
        $this->apiKeyRepository = $apiKeyRepository;
        $this->analyticsRepository = $analyticsRepository;
    }

    /**
     * @param RequestEvent $event
     * @throws NonUniqueResultException
     */
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if ($request->headers->has('x-api-key')) {
            $this->processApiKey($request, $path);
        }
    }

    /**
     * @param Request $request
     * @param string $path
     * @throws NonUniqueResultException
     */
    private function processApiKey(Request $request, string $path): void
    {
        $token = $request->headers->get('x-api-key');

        if ($apiKey = $this->apiKeyRepository->findByToken($token)) {
            $appId = $apiKey->getApp();

            $this->logToken('api_key', $appId, $token, $path);
        }
    }

    /**
     * @param string $type
     * @param int $appId
     * @param string $token
     * @param string $path
     */
    private function logToken(string $type, int $appId, string $token, string $path): void
    {
        $data = $this->getLogData($type, $this->cleanAppId($appId), $token, $path);

        $this->analyticsRepository->log($data);
    }

    /**
     * @param string $type
     * @param int $appId
     * @param string $token
     * @param string $path
     * @return array
     */
    private function getLogData(string $type, int $appId, string $token, string $path): array
    {
        return [
            'app' => $appId,
            'type' => $type,
            'token' => $this->maskString($token),
            'path' => $path,
            'timestamp' => time()
        ];
    }

    /**
     * @param string $string
     * @return string
     */
    public function maskString(string $string): string
    {
        return substr($string, 0, 4) . str_repeat("*", strlen($string) - 4);
    }

    /**
     * @param string $appId
     * @return string
     */
    private function cleanAppId(string $appId): string
    {
        if (strpos($appId, '_0') !== false) {
            $appId = trim(str_replace('_0', '', $appId), '0');
        }

        return $appId;
    }
}