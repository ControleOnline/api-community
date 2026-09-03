<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class QuotePeopleValidator extends ConstraintValidator
{
  public function validate($value, Constraint $constraint)
  {
    if (!$constraint instanceof QuotePeople) {
        throw new UnexpectedTypeException($constraint, QuotePeople::class);
    }

    if (null === $value || '' === $value) {
        return;
    }

    if (!is_array($value)) {
        throw new UnexpectedValueException($value, 'array');
    }

    $people = $value;

    if (!isset($people['people'])) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('{{ string }}' , '"people" is missing.')
        ->addViolation();
    }

    if (!isset($people['address'])) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('{{ string }}' , '"address" is missing.')
        ->addViolation();
    }

    if (!isset($people['contact'])) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('{{ string }}' , '"contact" is missing.')
        ->addViolation();
    }

    if (!is_numeric($people['people'])) {
      if (!filter_var($people['people'], FILTER_VALIDATE_EMAIL)) {
        $this->context->buildViolation($constraint->message)
          ->setParameter('{{ string }}' , '"people" is invalid.')
          ->addViolation();
      }
    }

    if (!is_array($people['address'])) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('{{ string }}' , '"address" is invalid.')
        ->addViolation();
    }

    if (!is_numeric($people['contact'])) {
      if (!filter_var($people['contact'], FILTER_VALIDATE_EMAIL)) {
        $this->context->buildViolation($constraint->message)
          ->setParameter('{{ string }}' , '"contact" is invalid.')
          ->addViolation();
      }
    }
  }
}
