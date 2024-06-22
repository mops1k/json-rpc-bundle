<?php

namespace JsonRpcBundle\MethodResolver;

use JsonRpcBundle\Request\JsonRpcRequest;

interface MethodResolverInterface
{
    public function resolve(JsonRpcRequest $jsonRpcRequest): mixed;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function add(string $name, object $method): void;
}
