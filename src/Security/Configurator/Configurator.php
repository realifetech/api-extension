<?php

namespace RL\Security\Configurator;

use Doctrine\Persistence\ObjectManager;
use RL\Security\AuthTenantResolver;
use RL\Security\Filter\TenantFilter;
use RL\Exception\NoApiTokenException;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Configurator
{
    /** @var ObjectManager */
    protected ObjectManager $defaultEntityManager;

    /** @var ObjectManager */
    protected ObjectManager $paymentEntityManager;

    /** @var TokenStorageInterface */
    protected TokenStorageInterface $tokenStorage;

    /** @var AuthTenantResolver */
    private AuthTenantResolver $authTenantResolver;

    /**
     * @param ObjectManager $defaultEntityManager
     * @param ObjectManager $paymentEntityManager
     * @param TokenStorageInterface $tokenStorage
     * @param AuthTenantResolver $authTenantResolver
     */
    public function __construct(
        ObjectManager $defaultEntityManager,
        ObjectManager $paymentEntityManager,
        TokenStorageInterface $tokenStorage,
        AuthTenantResolver $authTenantResolver
    ) {
        $this->defaultEntityManager = $defaultEntityManager;
        $this->paymentEntityManager = $paymentEntityManager;
        $this->tokenStorage = $tokenStorage;
        $this->authTenantResolver = $authTenantResolver;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        /** @var TenantFilter $defaultFilter */
        $defaultFilter = $this->defaultEntityManager->getFilters()->enable('tenant_filter');

        /** @var TenantFilter $paymentFilter */
        $paymentFilter = $this->paymentEntityManager->getFilters()->enable('tenant_filter');

        try {
            $this->authTenantResolver->resolveMeta($this->tokenStorage);

            $tenant = $this->authTenantResolver->getTenant();

            $defaultFilter->setParameter('currentTenant', $tenant);
            $paymentFilter->setParameter('currentTenant', $tenant);
        } catch (NoApiTokenException $e) {
            $defaultFilter->setParameter('currentTenant', 0);
            $paymentFilter->setParameter('currentTenant', 0);
        }
    }
}
