<?php

namespace RL\Service;

use LS\Apollo\UserManagement\Entity\App;
use LS\Apollo\App\Exception\EventDispatchException;
use LS\Apollo\UserManagement\Repository\AppConfigRepository;
use LS\Apollo\Hydration\Service\HydratorService;
use LS\Apollo\Queue\EventBus\EventBusClient;
use LS\Apollo\Queue\Producer\QueueProducer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class EventDispatcherService
{
    const APP_CONFIG        = 'event_dispatcher.enabled';
    const APP_CONFIG_EVENTS = 'event_dispatcher.events';

    const EVENT_ORDER_PAYMENT_SUCCESS          = 'order.payment_success';
    const EVENT_USER_ENGAGEMENT_CREATE_SUCCESS = 'user_engagement.create_success';
    const EVENT_USER_REGISTER_SUCCESS          = 'user.register_success';
    const EVENT_USER_LOGIN_SUCCESS             = 'user.login_success';
    const EVENT_USER_EMAIL_VERIFIED            = 'user_email.verified';
    const EVENT_DEVICE_REGISTER_SUCCESS        = 'device.register_success';
    const EVENT_DEVICE_TOKEN_UPDATE            = 'device_token.update';
    const DEVICE_CONSENT_UPDATE                = 'device_consent.update';
    const TICKET_AUTH_CREATED                  = 'ticket.auth_created';
    const TICKET_CREATED                       = 'ticket.created';
    const TICKET_SHARED                        = 'ticket.shared';
    const TICKET_REDEEMED                      = 'ticket.redeemed';
    const USER_UPDATE                          = 'user.update';
    const USER_SYNC_TICKETS                    = 'user.sync_tickets';
    const USER_BIRTHDAY                        = 'user.birthday';

    const EVENTS = [
        self::EVENT_ORDER_PAYMENT_SUCCESS,
        self::EVENT_USER_REGISTER_SUCCESS,
        self::EVENT_DEVICE_REGISTER_SUCCESS,
        self::TICKET_AUTH_CREATED,
        self::TICKET_CREATED,
        self::EVENT_USER_LOGIN_SUCCESS,
        self::DEVICE_CONSENT_UPDATE,
        self::USER_UPDATE,
        self::EVENT_USER_ENGAGEMENT_CREATE_SUCCESS,
        self::EVENT_DEVICE_TOKEN_UPDATE,
        self::TICKET_SHARED,
        self::TICKET_REDEEMED,
        self::USER_SYNC_TICKETS,
        self::USER_SYNC_TICKETS,
        self::USER_BIRTHDAY,
        self::EVENT_USER_EMAIL_VERIFIED
    ];

    private $queueProducer;
    private $hydratorService;
    private $queueUrl;
    private $appConfigRepository;
    private $logger;
    private $eventBusClient;
    private $serializer;

    public function __construct(
        QueueProducer $queueProducer,
        HydratorService $hydratorService,
        AppConfigRepository $appConfigRepository,
        LoggerInterface $logger,
        EventBusClient $eventBusClient,
        SerializerInterface $serializer,
        string $queueUrl
    ) {
        $this->queueProducer       = $queueProducer;
        $this->hydratorService     = $hydratorService;
        $this->appConfigRepository = $appConfigRepository;
        $this->logger              = $logger;
        $this->eventBusClient      = $eventBusClient;
        $this->serializer          = $serializer;
        $this->queueUrl            = $queueUrl;
    }

    public function putEvent(
        App $app,
        string $type,
        string $action,
        $new = null,
        $old = null,
        array $groups = [],
        string $prefix = '',
        array $meta = []
    ) {

        $name = $prefix . $type . '.' . $action;

        $configuredEvents = $this->getConfiguredEvents($app);
        $appId            = $app->getId();

        if (!(int)$this->appConfigRepository->getValueByAppAndKey($app, self::APP_CONFIG)) {
            $this->logger->info("EVENT DISPATCHER: event dispatcher disabled for the app: {$appId}");
            return;
        }

        if (!$this->eventIsConfigured($configuredEvents, $name)) {
            $this->logger->info("EVENT DISPATCHER: Event {$name} ignored due to event configuration for the
                app: {$appId}");
            return;
        }

        $meta = $this->getMeta($meta);

        $event = [
            "type"    => $type,
            "action"  => $action,
            "new"     => $new,
            "old"     => $old,
            "version" => 1,
            "meta"    => $meta
        ];

        try {
            $json = $this->serializer->serialize($event, 'json', [
                'groups' => array_merge($groups, ['device:event', 'user:event']),
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true
            ]);
            $this->eventBusClient->putEvent($app, $name, $json);

            $this->dispatchOld($app, $name, $new, $groups);
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
        }
    }

    /**
     * @param App    $app
     * @param string $event
     * @param array  $payload
     * @param array  $hydrationGroups
     */
    public function dispatch(
        App $app,
        string $event,
        array $payload,
        array $hydrationGroups = []
    ) {
        $event = explode('.', $event);

        $this->putEvent($app, $event[0], $event[1], $payload, null, $hydrationGroups);
    }


    /**
     * @param App    $app
     * @param string $event
     * @param array  $payload
     * @param array  $hydrationGroups
     * @deprecated this is replaced but putEvent
     */
    public function dispatchOld(
        App $app,
        string $event,
        array $payload,
        array $hydrationGroups = []
    ) {
        $appId = $app->getId();

        try {
            $this->validate($event);
            $payload = $this->hydratorService->toArray($payload, $hydrationGroups);
            $this->queueProducer->produceJsonMessage($this->queueUrl, compact('appId', 'event', 'payload'));

            $this->logger->info("EVENT DISPATCHER: Event {$event} dispatched for appId {$appId}");
        } catch (EventDispatchException $e) {
            $this->logger->critical("EVENT DISPATCHER ERROR: Event {$event} is not a valid event");
        }
    }

    /**
     * @param string $event
     * @throws EventDispatchException
     */
    private function validate(string $event): void
    {
        if (!in_array($event, self::EVENTS) && !preg_match('/entity\..*/', $event)) {
            throw new EventDispatchException('event_dispatch.event_not_defined');
        }
    }

    /**
     * @param App $app
     * @return array
     */
    protected function getConfiguredEvents(App $app): array
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
