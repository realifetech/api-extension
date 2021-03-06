<?php

namespace RL\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use RL\Security\AuthTenantResolver;
use RL\Service\EventDispatcherService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class EntityActivitySubscriber implements EventSubscriber
{
    const PERSIST_EVENT = 'persist';
    const UPDATE_EVENT  = 'update';
    const REMOVE_EVENT  = 'remove';

    /** @var ContainerInterface */
    private ContainerInterface $container;

    /** @var AuthTenantResolver */
    private AuthTenantResolver $authTenantResolver;

    /** @var PropertyAccessor */
    private PropertyAccessor $propertyAccessor;

    public function __construct(
        ContainerInterface $container,
        AuthTenantResolver $authTenantResolver
    ) {
        $this->container = $container;
        $this->authTenantResolver = $authTenantResolver;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @return array
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::preRemove,
            Events::preUpdate,
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();
        /** this is to make sure relations are persisted */
        $args->getObjectManager()->flush();

        $this->processEvent(self::PERSIST_EVENT, $object);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();

        $this->processEvent(self::REMOVE_EVENT, null, $object);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args): void
    {
        /** @var PreUpdateEventArgs $args */
        $object  = $args->getObject();

        $changes = $args->getEntityChangeSet();

        $old = clone $object;

        foreach ($changes as $key => $value) {
            $this->propertyAccessor->setValue($old, $key, $value[0]);
        }

        $this->processEvent(self::UPDATE_EVENT, $object, $old);
    }

    private function getEventDispatcher(): ?object
    {
        return $this->container->get(EventDispatcherService::class);
    }

    /**
     * @param string $object
     * @return string
     */
    private function getEntitySnakeCase(string $object): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $object));
    }

    /**
     * @param object $object
     * @return string
     */
    private function getShortName(object $object): string
    {
        return (new \ReflectionClass($object))->getShortName();
    }

    /**
     * @param string $action
     * @param object|null $new
     * @param object|null $old
     */
    private function processEvent(string $action, object $new = null, object $old = null): void
    {
        if (!$new) {
            $object = $old;
        } else {
            $object = $new;
        }

        $tenant = $this->authTenantResolver->getTenant();
        $entityName = $this->getShortName($object);
        $type = $this->getEntitySnakeCase($entityName);

        $this->getEventDispatcher()
            ->putEvent(
                $tenant,
                $type,
                $action,
                [$type => $new],
                [$type => $old],
                [$type . ':event'],
                'entity.'
            );
    }
}
