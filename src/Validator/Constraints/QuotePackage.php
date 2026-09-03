<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class QuotePackage extends Constraint
{
    public $message = 'Field "{{ string }}" on package "{{ package }}" is not valid.';
}
