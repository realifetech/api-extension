<?php

namespace RL\EventListener;

use ApiPlatform\Core\EventListener\EventPriorities;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PropertyAccess\PropertyAccessor;

final class EntityPreWriteSubscriber implements EventSubscriberInterface
{
    // TODO remove
    const APP_ID = 27;
    const TENANT_ID = 27;

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => [
                ['attachApp', EventPriorities::PRE_VALIDATE],
                ['attachTenant', EventPriorities::PRE_VALIDATE]
            ],
        ];
    }

    public function attachApp(ViewEvent $event)
    {
        $entity = $event->getControllerResult();
        $propertyAccessor = new PropertyAccessor();

        if (!is_object($entity) || !$propertyAccessor->isWritable($entity, 'app')) {
            return;
        }

        $propertyAccessor->setValue($entity, 'app', self::APP_ID);
    }

    public function attachTenant(ViewEvent $event)
    {
        $entity = $event->getControllerResult();
        $propertyAccessor = new PropertyAccessor();

        if (!$propertyAccessor->isWritable($entity, 'tenant')) {
            return;
        }

        $propertyAccessor->setValue($entity, 'tenant', self::TENANT_ID);
    }
}
