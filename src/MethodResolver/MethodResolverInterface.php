<?php

namespace JsonRpcBundle\MethodResolver;

use JsonRpcBundle\Request\JsonRpcRequest;

interface MethodResolverInterface
{
    public function resolve(JsonRpcRequest $jsonRpcRequest, ?string $namespace = null): mixed;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function add(string $name, object $method, ?string $namespace = null): void;

    public function getMethodsList(): array;
}
