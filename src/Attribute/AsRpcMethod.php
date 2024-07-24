<?php

namespace JsonRpcBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class AsRpcMethod
{
    public function __construct(public string $methodName, public ?string $namespace = null)
    {
    }
}
