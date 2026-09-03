<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ContactDataValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ContactData) {
            throw new UnexpectedTypeException($constraint, ContactData::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        $contact = $value;

        if (!isset($contact['name']) || empty($contact['name']))
          $this->context->buildViolation($constraint->message)
            ->setParameter('{{ string }}' , 'name')
            ->addViolation();

        if (!isset($contact['email']) || !filter_var($contact['email'], FILTER_VALIDATE_EMAIL))
          $this->context->buildViolation($constraint->message)
            ->setParameter('{{ string }}' , 'email')
            ->addViolation();

        if (!isset($contact['phone']) || preg_match('/^[0-9]{6,11}$/', $contact['phone']) !== 1)
          $this->context->buildViolation($constraint->message)
            ->setParameter('{{ string }}' , 'phone')
            ->addViolation();
    }
}
