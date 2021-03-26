<?php

namespace RL\Service;

use RL\Exception\EventDispatchException;
use RL\Repository\AppConfigRepository;
use RL\EventBus\EventBridgeClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class EventDispatcherService
{
    const APP_CONFIG = 'event_dispatcher.enabled';
    const APP_CONFIG_EVENTS = 'event_dispatcher.events';

    /** @var AppConfigRepository */
    private AppConfigRepository $appConfigRepository;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var EventBridgeClient */
    private EventBridgeClient $eventBridgeClient;

    /** @var SerializerInterface */
    private SerializerInterface $serializer;

    public function __construct(
        AppConfigRepository $appConfigRepository,
        LoggerInterface $logger,
        EventBridgeClient $eventBridgeClient,
        SerializerInterface $serializer
    ) {
        $this->appConfigRepository = $appConfigRepository;
        $this->logger = $logger;
        $this->eventBridgeClient = $eventBridgeClient;
        $this->serializer = $serializer;
    }

    /**
     * @param int $app
     * @param string $type
     * @param string $action
     * @param object|null $new
     * @param object|null $old
     * @param array $groups
     * @param string $prefix
     */
    public function putEvent(
        int $app,
        string $type,
        string $action,
        $new = null,
        $old = null,
        array $groups = [],
        string $prefix = ''
    ) {
        $name = $prefix . $type . '.' . $action;

        $configuredEvents = $this->getConfiguredEvents($app);

        if (!(int)$this->appConfigRepository->getValueByAppAndKey($app, self::APP_CONFIG)) {
            $this->logger->info("EVENT DISPATCHER: event dispatcher disabled for the app: {$app}");

            return;
        }

        if (!$this->eventIsConfigured($configuredEvents, $name)) {
            $this->logger->info("EVENT DISPATCHER: Event {$name} ignored due to event configuration for the app: {$app}");

            return;
        }

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

            $this->eventBridgeClient->putEvent($app, $name, $json);
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
        }
    }

    /**
     * @param int $app
     * @return array
     */
    protected function getConfiguredEvents(int $app): array
    {
        if ($configuredEvents = $this->appConfigRepository->getValueByAppAndKey($app, self::APP_CONFIG_EVENTS)) {
            $configuredEvents = json_decode($configuredEvents, true);
        }

        $configuredEvents = $configuredEvents ?? [];

        return $configuredEvents;
    }

    /**
     * @param array  $configuredEvents
     * @param string $event
     * @return bool
     */
    private function eventIsConfigured(array $configuredEvents, string $event): bool
    {
        $inConfig = isset($configuredEvents[$event]);
        if ($inConfig && ((bool)$configuredEvents[$event] === false)) {
            return false;
        }

        if (!$inConfig && isset($configuredEvents['entity.*']) && preg_match(
                '/entity\.*/',
                $event
            ) && $configuredEvents['entity.*'] === false) {

            return false;
        }

        return true;
    }
}
