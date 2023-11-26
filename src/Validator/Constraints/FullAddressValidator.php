<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class FullAddressValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof FullAddress) {
            throw new UnexpectedTypeException($constraint, FullAddress::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        $address = $value;

        if (!isset($address['country']) || !is_string($address['country']))
          $this->context->buildViolation($constraint->message)
            ->setParameter('{{ string }}' , 'country')
            ->addViolation();

        if (!isset($address['state']) || !is_string($address['state']))
          $this->context->buildViolation($constraint->message)
            ->setParameter('{{ string }}' , 'state')
            ->addViolation();

        if (!isset($address['city']) || !is_string($address['city']))
          $this->context->buildViolation($constraint->message)
            ->setParameter('{{ string }}' , 'city')
            ->addViolation();

       if (!isset($address['district']) || !is_string($address['district']))
         $this->context->buildViolation($constraint->message)
           ->setParameter('{{ string }}' , 'district')
           ->addViolation();

       if (!isset($address['street']) || !is_string($address['street']))
         $this->context->buildViolation($constraint->message)
           ->setParameter('{{ string }}' , 'street')
           ->addViolation();

       if (!isset($address['number']) || !is_string($address['number']))
         $this->context->buildViolation($constraint->message)
           ->setParameter('{{ string }}' , 'number')
           ->addViolation();

       if (!isset($address['postal_code']) || (preg_match('/^[0-9]{8}$/', $address['postal_code']) !== 1))
         $this->context->buildViolation($constraint->message)
           ->setParameter('{{ string }}' , 'postal_code')
           ->addViolation();
    }
}
