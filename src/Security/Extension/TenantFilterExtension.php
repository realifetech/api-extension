<?php


namespace RL\Security\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TenantFilterExtension implements QueryItemExtensionInterface
{
    /** @var Reader */
    private Reader $reader;

    /** @var ObjectManager */
    private ObjectManager $em;

    /** @var TokenStorageInterface */
    private TokenStorageInterface $tokenStorage;

    public function __construct(Reader $reader, ObjectManager $em, TokenStorageInterface $tokenStorage)
    {
        $this->reader = $reader;
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        string $operationName = null,
        array $context = []
    ): void {
        $this->enableTenantFilter();
    }

    private function enableTenantFilter(): void
    {
        $currentTenant = $this->getTenant();

        if (!$currentTenant) {
            return;
        }

        $filter = $this->em->getFilters()->enable('tenant_filter');
        $filter->setParameter('currentTenant', $currentTenant);
        $filter->setAnnotationReader($this->reader);
    }

    private function getTenant()
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return null;
        }

        return $token->getUser();
    }
}
