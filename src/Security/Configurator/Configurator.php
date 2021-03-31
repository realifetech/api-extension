<?php

namespace RL\Security\Configurator;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Persistence\ObjectManager;
use RL\Exception\NoApiTokenException;
use RL\Security\AuthTenantResolver;
use RL\Security\Filter\AppFilter;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Configurator
{
    /** @var ObjectManager */
    protected ObjectManager $em;

    /** @var TokenStorageInterface */
    protected TokenStorageInterface $tokenStorage;

    /** @var Reader */
    protected Reader $reader;

    /** @var AuthTenantResolver */
    private AuthTenantResolver $authTenantResolver;

    /**
     * @param ObjectManager $em
     * @param TokenStorageInterface $tokenStorage
     * @param Reader $reader
     * @param AuthTenantResolver $authTenantResolver
     */
    public function __construct(
        ObjectManager $em,
        TokenStorageInterface $tokenStorage,
        Reader $reader,
        AuthTenantResolver $authTenantResolver
    ) {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->reader = $reader;
        $this->authTenantResolver = $authTenantResolver;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        /** @var AppFilter $filter */
        $filter = $this->em->getFilters()->enable('tenant_filter');

        try {
            $this->authTenantResolver->resolveMeta($this->tokenStorage);

            $tenant = $this->authTenantResolver->getTenant();

            if ($tenant) {
                $filter->setParameter('currentTenant', $tenant);

                $filter->setAnnotationReader($this->reader);
            }
        } catch (NoApiTokenException $e) {
            $filter->setParameter('currentTenant', 0);
        }
    }
}
