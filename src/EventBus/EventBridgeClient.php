<?php

namespace RL\EventBus;

use Aws\Exception\AwsException;
use Aws\EventBridge\EventBridgeClient as BaseEventBridgeClient;
use Psr\Log\LoggerInterface;

class EventBridgeClient
{
    /** @var BaseEventBridgeClient */
    private BaseEventBridgeClient $client;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var string */
    private string $env;

    /** @var string */
    private string $source;

    public function __construct(BaseEventBridgeClient $client, LoggerInterface $logger, string $env, string $source)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->env = $env;
        $this->source = $source;
    }

    public function putEvent(int $tenant, string $name, string $detail)
    {
        try {
            $result = $this->client->putEvents([
                'Entries' => [
                    [
                        'Detail' => $detail,
                        'DetailType' => $name,
                        'EventBusName' => $this->getEventBusName(),
                        'Resources' => ['apps/' . $tenant],
                        'Source' => $this->source,
                        'Time' => time(),
                    ],
                ]
            ]);

            if ($result->get('FailedEntryCount') !== 0) {
                $this->logger->critical("Error putting event into event bus: " . json_encode($result->toArray()));
            }
        } catch (AwsException $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @return string
     */
    private function getEventBusName(): string
    {
        return 'platform.' . $this->env;
    }
}
