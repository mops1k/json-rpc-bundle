<?php

namespace JsonRpcBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class RpcMethodContract
{
    public function __construct(
        public string $className
    ) {
    }
}
