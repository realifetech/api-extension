<?php

namespace RL\EventListener;

use ApiPlatform\Core\EventListener\EventPriorities;
use RL\Security\AuthTenantResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PropertyAccess\PropertyAccessor;

final class EntityPreWriteSubscriber implements EventSubscriberInterface
{
    /** @var AuthTenantResolver */
    private AuthTenantResolver $authTenantResolver;

    public function __construct(AuthTenantResolver $authTenantResolver)
    {
        $this->authTenantResolver = $authTenantResolver;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [
                ['attachTenant', EventPriorities::PRE_VALIDATE]
            ],
        ];
    }

    public function attachTenant(ViewEvent $event)
    {
        $entity = $event->getControllerResult();
        $propertyAccessor = new PropertyAccessor();

        if (!$propertyAccessor->isWritable($entity, 'tenant')) {
            return;
        }

        $tenant = $this->authTenantResolver->getTenant();

        if (!$tenant) {
            return;
        }

        $propertyAccessor->setValue($entity, 'tenant', $tenant);
    }
}
