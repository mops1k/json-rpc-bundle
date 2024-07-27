<?php

namespace JsonRpcBundle\Tests\Stubs\Method;

use JsonRpcBundle\Attribute\AsRpcMethod;

/**
 * Test method with namespace.
 */
#[AsRpcMethod(
    methodName: 'testMethodWithNamespace',
    namespace: 'testNamespace'
)]
class TestMethodWithNamespace
{
    public function __invoke(): string
    {
        return 'Method with namespace running successfully.';
    }
}
