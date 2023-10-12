<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class QuoteAddress extends Constraint
{
    public $message = 'Parameter "{{ string }}" is missing.';
}
