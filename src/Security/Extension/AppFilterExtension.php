<?php

namespace RL\Security\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AppFilterExtension implements QueryItemExtensionInterface
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

        if (!$this->isApiRequest($context)) {
            return;
        }

        $this->enableAppFilter();
    }

    private function isApiRequest(array $context): bool
    {
        return (bool) preg_match('/\/v\d+\//', $context['request_uri'] ?? null);
    }

    private function enableAppFilter(): void
    {
        $currentApp = $this->getUser();

        if (!$currentApp) {
            return;
        }

        $filter = $this->em->getFilters()->enable('app_filter');
        $filter->setParameter('currentApp', $currentApp->getId());
        $filter->setAnnotationReader($this->reader);
    }

    private function getUser()
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return null;
        }

        return $token->getUser();
    }
}
