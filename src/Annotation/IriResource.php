<?php

namespace RL\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class IriResource
{
    /**
     * @var string
     * @Required
     */
    public string $resourcePrefix;

    /** @var bool */
    public bool $crossMicroservice = false;

    /** @var string */
    public string $subpropertyName;
}
