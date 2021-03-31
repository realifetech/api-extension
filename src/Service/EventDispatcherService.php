<?php

namespace RL\Service;

use RL\EventBus\EventBridgeClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class EventDispatcherService
{
    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var EventBridgeClient */
    private EventBridgeClient $eventBridgeClient;

    /** @var SerializerInterface */
    private SerializerInterface $serializer;

    public function __construct(
        LoggerInterface $logger,
        EventBridgeClient $eventBridgeClient,
        SerializerInterface $serializer
    ) {
        $this->logger = $logger;
        $this->eventBridgeClient = $eventBridgeClient;
        $this->serializer = $serializer;
    }

    /**
     * @param int $tenant
     * @param string $type
     * @param string $action
     * @param object|null $new
     * @param object|null $old
     * @param array $groups
     * @param string $prefix
     */
    public function putEvent(
        int $tenant,
        string $type,
        string $action,
        $new = null,
        $old = null,
        array $groups = [],
        string $prefix = ''
    ) {
        $name = $prefix . $type . '.' . $action;

        $event = [
            "type"    => $type,
            "action"  => $action,
            "new"     => $new,
            "old"     => $old,
            "version" => 1,
            "meta"    => []
        ];

        try {
            $json = $this->serializer->serialize($event, 'json', [
                'groups' => array_merge($groups, ['device:event', 'user:event']),
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true
            ]);

            $this->eventBridgeClient->putEvent($tenant, $name, $json);
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
        }
    }
}
