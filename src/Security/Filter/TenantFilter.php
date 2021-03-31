<?php

namespace RL\Security\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Common\Annotations\Reader;

class TenantFilter extends SQLFilter
{
    /** @var Reader */
    protected Reader $reader;

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

        if ($this->hasParameter('tenant')) {
            $fieldName = 'tenant';
        } else {
            $fieldName = 'app_id';
        }

        $currentTenant = $this->getParameter('currentTenant');

        if (empty($fieldName)) {
            return '';
        }

        if ($currentTenant) {
            if ($query) {
                $query = $query . " AND " . $targetTableAlias . '.' . $fieldName . "=" . $currentTenant;
            } else {
                $query = $targetTableAlias . '.' . $fieldName . "=" . $currentTenant;
            }
        }

        return $query;
    }

    /**
     * @param Reader $reader
     */
    public function setAnnotationReader(Reader $reader)
    {
        $this->reader = $reader;
    }
}
