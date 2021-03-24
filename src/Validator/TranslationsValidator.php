<?php

namespace RL\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TranslationsValidator extends ConstraintValidator
{
    public function validate($translations, Constraint $constraint)
    {
        if (!is_array($translations)) {
            return;
        }

        foreach ($translations as $key => $translation) {
            if (!isset($translation['language']) || empty($translation['language'])) {
                $this->context->buildViolation("'language' is a required property.")
                    ->atPath("translation[$key]")
                    ->addViolation();
            }

            if (!isset($translation['title']) || empty($translation['title'])) {
                $this->context->buildViolation("'title' is a required property.")
                    ->atPath("translation[$key]")
                    ->addViolation();
            }
        }
    }
}
