# JSON-RPC Server bundle

This bundle provide an easy way to implement json-rpc server with fully specification supporting.

[JSON-RPC](https://www.jsonrpc.org/specification) specification is fully compatible
with [CQRS](https://en.wikipedia.org/wiki/Command_Query_Responsibility_Segregation) architecture

## Install

```bash
composer require mops1k/json-rpc-bundle
```

## Usage

To create method you have to create invokable class with
attribute [`\JsonRpcBundle\Attribute\AsRpcMethod`](./src/Attribute/AsRpcMethod.php), where `methodName` constructor
parameter must contain method name. Example:

```php
<?php

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
}
```

Bundle provide possibility to provide params to your DTO class. For these feature you have to add
attribute [`\JsonRpcBundle\Attribute\RpcMethodContract`](./src/Attribute/RpcMethodContract.php).
DTO passed supports validation through [symfony/validator](https://symfony.com/doc/current/validation.html).
Example:

```php
<?php

use JsonRpcBundle\Attribute\AsRpcMethod;
use JsonRpcBundle\Attribute\RpcMethodContract;
use JsonRpcBundle\Tests\Stubs\Contract\Contract;
use Symfony\Component\Validator\Constraints as Assert;

readonly class Contract
{
    public function __construct(
        #[Assert\GreaterThanOrEqual(0)]
        public int $id,
        #[Assert\NotBlank(allowNull: false)]
        public ?string $text = null,
    ) {
    }
}

#[AsRpcMethod('testMethodWithContract')]
#[RpcMethodContract(Contract::class)]
class TestMethodWithContract
{
    public function __invoke(Contract $contract): array
    {
        return [
            'id' => $contract->id,
            'text' => $contract->text,
        ];
    }
}
```

If you don't want to use DTO, you still able to validate method parameters and set its groups. In this case you need to
implement your method class
from [`\JsonRpcBundle\MethodResolver\ValidateMethodParametersInterface`](./src/MethodResolver/ValidateMethodParametersInterface.php).
Example:

```php
<?php

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
        ], groups: ['rpc']);
    }

    public function validationGroups(): array|string|GroupSequence|null
    {
        return ['rpc'];
    }
}
```

Json rpc supports notification requests what does not return any response. To make your method as notification, just
add `void` in `__invoke` return type hint.
