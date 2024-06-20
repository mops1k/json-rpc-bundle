<?php

namespace JsonRpcBundle\Exceptions;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationList;

class JsonRcpInvalidRequestException extends BadRequestHttpException implements JsonRpcExceptionInterface
{
    public function __construct(
        public readonly ConstraintViolationList $errors,
        private readonly int|string|null $id = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            message: FaultCode::INVALID_REQUEST->getMessage(),
            previous: $previous,
            code: FaultCode::INVALID_REQUEST->value
        );
    }

    public function getErrors(): ConstraintViolationList
    {
        return $this->errors;
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }
}
