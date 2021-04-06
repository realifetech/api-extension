<?php

namespace RL\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class TenantAware
{
    /** @Required */
    public string $tenantFieldName;
}
