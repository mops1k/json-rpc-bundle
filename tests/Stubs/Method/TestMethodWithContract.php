<?php

namespace JsonRpcBundle\Tests\Stubs\Method;

use JsonRpcBundle\Attribute\AsRpcMethod;
use JsonRpcBundle\Attribute\RpcMethodContract;
use JsonRpcBundle\Tests\Stubs\Contract\Contract;

#[AsRpcMethod('testMethodWithContract')]
#[RpcMethodContract(Contract::class)]
class TestMethodWithContract
{
    public function __invoke(Contract $contract): Contract
    {
        return $contract;
    }
}
