<?php

namespace JsonRpcBundle\Exceptions;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class JsonRpcInvalidParamsException extends BadRequestHttpException implements JsonRpcExceptionInterface
{
    public function __construct(
        public readonly ConstraintViolationListInterface $errors,
        private readonly int|string|null $id = null,
        ?\Exception $previous = null
    ) {
        parent::__construct(
            message: FaultCode::INVALID_PARAMS->getMessage(),
            previous: $previous,
            code: FaultCode::INVALID_PARAMS->value
        );
    }

    public function getErrors(): ConstraintViolationListInterface
    {
        return $this->errors;
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }
}
