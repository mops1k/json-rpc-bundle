<?php

namespace JsonRpcBundle\Exceptions;

interface JsonRpcExceptionInterface
{
    public function getId(): int|string|null;
}
