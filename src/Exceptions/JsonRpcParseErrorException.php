<?php

namespace JsonRpcBundle\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class JsonRpcParseErrorException extends UnprocessableEntityHttpException implements JsonRpcExceptionInterface
{
    public function __construct(
        protected mixed $data = null,
        ?\Exception $previous = null
    ) {
        parent::__construct(
            message: FaultCode::PARSE_ERROR->getMessage(),
            previous: $previous,
            code: FaultCode::PARSE_ERROR->value
        );
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getId(): int|string|null
    {
        return null;
    }
}
