<?php

namespace JsonRpcBundle\Tests\Stubs\Method;

use JsonRpcBundle\Attribute\AsRpcMethod;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

#[AsRpcMethod('testMethodWithoutContract')]
class TestMethodWithoutContract
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
}
