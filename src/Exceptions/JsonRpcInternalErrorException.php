<?php

namespace JsonRpcBundle\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class JsonRpcInternalErrorException extends HttpException implements JsonRpcExceptionInterface
{
    public function __construct(
        private readonly mixed $data = null,
        private readonly int|string|null $id = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            statusCode: 500,
            message: FaultCode::INTERNAL_ERROR->getMessage(),
            previous: $previous,
            code: FaultCode::INTERNAL_ERROR->value
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
