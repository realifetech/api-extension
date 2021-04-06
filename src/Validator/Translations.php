<?php

namespace RL\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Translations extends Constraint
{
    public string $message = '';

    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
