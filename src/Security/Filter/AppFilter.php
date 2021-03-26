<?php

namespace RL\Security\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Common\Annotations\Reader;

/**
 * Class AppFilter
 */
class AppFilter extends SQLFilter
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

        $currentApp = $this->getParameter('currentApp');

        if (empty($fieldName)) {
            return '';
        }

        if ($currentApp && $fieldName != "id") {
            if ($query) {
                $query = $query . " AND " . $targetTableAlias . '.' . $fieldName . "=" . $currentApp;
            } else {
                $query = $targetTableAlias . '.' . $fieldName . "=" . $currentApp;
            }
        }

        return $query;
    }

    /**
     * @param Reader $reader
     * @return Reader
     */
    public function setAnnotationReader(Reader $reader): Reader
    {
        $this->reader = $reader;
    }
}
