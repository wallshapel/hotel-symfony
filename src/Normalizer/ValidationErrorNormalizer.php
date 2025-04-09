<?php

namespace App\Normalizer;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationErrorNormalizer
{
    public function normalize(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $errors;
    }
}
