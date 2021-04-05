<?php

namespace RL\Security\Configurator;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Persistence\ObjectManager;
use RL\Security\AuthTenantResolver;
use RL\Security\Filter\TenantFilter;
use RL\Exception\NoApiTokenException;
use Symfony\Component\HttpKernel\KernelEvents;
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
     * @param Reader $reader
     * @param TokenStorageInterface $tokenStorage
     * @param AuthTenantResolver $authTenantResolver
     */
    public function __construct(
        ObjectManager $em,
        Reader $reader,
        TokenStorageInterface $tokenStorage,
        AuthTenantResolver $authTenantResolver
    ) {
        $this->em = $em;
        $this->reader = $reader;
        $this->tokenStorage = $tokenStorage;
        $this->authTenantResolver = $authTenantResolver;
    }

    public function onKernelRequest(KernelEvents $event)
    {
        /** @var TenantFilter $filter */
        $filter = $this->em->getFilters()->enable('tenant_filter');

        try {
            $this->authTenantResolver->resolveMeta($this->tokenStorage);

            $tenant = $this->authTenantResolver->getTenant();

            $filter->setParameter('currentTenant', $tenant);
            $filter->setAnnotationReader($this->reader);
        } catch (NoApiTokenException $e) {
            $filter->setParameter('currentTenant', 0);
        }
    }
}
