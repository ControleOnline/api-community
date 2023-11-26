<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ContactData extends Constraint
{
    public $message = 'Parameter "{{ string }}" is not valid.';
}
