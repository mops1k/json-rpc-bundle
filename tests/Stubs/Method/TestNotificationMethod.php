<?php

namespace JsonRpcBundle\Tests\Stubs\Method;

use JsonRpcBundle\Attribute\AsRpcMethod;

#[AsRpcMethod('testNotificationMethod')]
class TestNotificationMethod
{
    public function __invoke(): void
    {
    }
}
