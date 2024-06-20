<?php

namespace JsonRpcBundle\Exceptions;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class JsonRpcMethodNotExistsException extends BadRequestHttpException implements JsonRpcExceptionInterface
{
    public function __construct(
        protected mixed $data = null,
        private readonly int|string|null $id = null,
        ?\Exception $previous = null
    ) {
        parent::__construct(
            message: FaultCode::METHOD_NOT_FOUND->getMessage(),
            previous: $previous,
            code: FaultCode::METHOD_NOT_FOUND->value
        );
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }
}
