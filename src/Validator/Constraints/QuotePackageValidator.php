<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class QuotePackageValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof QuotePackage) {
            throw new UnexpectedTypeException($constraint, QuotePackage::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        // value can be float. Ex: packages = 10.45 or "10.45"
        if (is_numeric($value))
          return;

        // or
        // value can be an array. Ex: packages = [{}]
        if (!is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        $packages = $value;

        foreach ($packages as $key => $package) {
          if (empty($package) || !is_array($package))
            throw new UnexpectedValueException($package, 'package');

            if ((!isset($package['qtd'])    || !is_integer($package['qtd']))   || $package['qtd'] < 1) {
              $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}' , 'qtd')
                ->setParameter('{{ package }}', $key + 1)
                ->addViolation();
            }

            if ((!isset($package['weight']) || !is_numeric($package['weight'])) || $package['weight'] <= 0) {
              $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}' , 'weight')
                ->setParameter('{{ package }}', $key + 1)
                ->addViolation();
            }

            if ((!isset($package['height']) || !is_numeric($package['height'])) || $package['height'] <= 0) {
              $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}' , 'height')
                ->setParameter('{{ package }}', $key + 1)
                ->addViolation();
            }

            if ((!isset($package['width'])  || !is_numeric($package['width']))  || $package['width']  <= 0) {
              $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}' , 'width')
                ->setParameter('{{ package }}', $key + 1)
                ->addViolation();
            }

            if ((!isset($package['depth'])  || !is_numeric($package['depth']))  || $package['depth']   <= 0) {
              $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}' , 'depth')
                ->setParameter('{{ package }}', $key + 1)
                ->addViolation();
            }
        }
    }
}
