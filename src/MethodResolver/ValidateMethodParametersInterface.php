<?php

namespace JsonRpcBundle\MethodResolver;

use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\GroupSequence;

interface ValidateMethodParametersInterface
{
    public function configureValidation(): Collection;

    public function validationGroups(): array|string|GroupSequence|null;
}
