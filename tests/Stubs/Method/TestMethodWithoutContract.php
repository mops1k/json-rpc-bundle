<?php

namespace JsonRpcBundle\Tests\Stubs\Method;

use JsonRpcBundle\Attribute\AsRpcMethod;
use JsonRpcBundle\MethodResolver\ValidateMethodParametersInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\GroupSequence;

#[AsRpcMethod('testMethodWithoutContract')]
class TestMethodWithoutContract implements ValidateMethodParametersInterface
{
    public function __invoke(int $id): int
    {
        return $id;
    }

    public function configureValidation(): Collection
    {
        return new Collection([
            'id' => [
                new GreaterThanOrEqual(0),
            ],
        ]);
    }

    public function validationGroups(): array|string|GroupSequence|null
    {
        return null;
    }
}
