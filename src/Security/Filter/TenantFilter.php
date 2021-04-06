<?php

namespace RL\Security\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Common\Annotations\Reader;
use RL\Annotation\TenantAware;

class TenantFilter extends SQLFilter
{
    /** @var Reader */
    protected Reader $reader;

    /**
     * @param Reader $reader
     */
    public function setAnnotationReader(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param ClassMetadata $targetEntity
     * @param string $targetTableAlias
     * @return string
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $query = '';

        if (empty($this->reader)) {
            return '';
        }

        /** @var TenantAware $tenantAware */
        $tenantAware = $this->reader->getClassAnnotation($targetEntity->getReflectionClass(), TenantAware::class);

        if (!$tenantAware) {
            return '';
        }

        $fieldName = $tenantAware->tenantFieldName;

        if (empty($fieldName)) {
            return '';
        }

        $currentTenant = $this->getParameter('currentTenant');

        if ($currentTenant && $fieldName != "id") {
            $query = $targetTableAlias . '.' . $fieldName . "=" . $currentTenant;
        }

        return $query;
    }
}
