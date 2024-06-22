<?php

namespace JsonRpcBundle\Exceptions;

interface JsonRpcExceptionInterface extends \Throwable
{
    public function getId(): int|string|null;
}
