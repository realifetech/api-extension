<?php

namespace RL\Security\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;

class AppFilterExtension implements QueryItemExtensionInterface
{
    const DEFAULT_APP = 27;

    /** @var Reader */
    private Reader $reader;

    /** @var ObjectManager */
    private ObjectManager $em;

    public function __construct(Reader $reader, ObjectManager $em)
    {
        $this->reader = $reader;
        $this->em = $em;
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
        $filter = $this->em->getFilters()->enable('app_filter');
        $filter->setParameter('currentApp', self::DEFAULT_APP);
        $filter->setAnnotationReader($this->reader);
    }
}
