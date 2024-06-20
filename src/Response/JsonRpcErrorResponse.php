<?php

namespace JsonRpcBundle\Response;

use JsonRpcBundle\Exceptions\JsonRpcExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolation;

class JsonRpcErrorResponse extends JsonResponse
{
    public function __construct(
        JsonRpcExceptionInterface|\Throwable|null $exception,
        int|string|null $id,
        int $status = 200,
        array $headers = ['Content-Type' => 'application/json']
    ) {
        parent::__construct(null, $status, $headers);

        $this->setResult($exception, $id);
    }

    public function setResult(JsonRpcExceptionInterface $exception, int|string|null $id): void
    {
        $result = [
            'jsonrpc' => '2.0',
            'error' => [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ],
            'id' => $id,
        ];

        if (\method_exists($exception, 'getData')) {
            $result['error']['data'] = $exception->getData();
        }

        if (\method_exists($exception, 'getErrors')) {
            $errors = [];
            /** @var ConstraintViolation $error */
            foreach ($exception->getErrors() as $error) {
                $errors[$error->getPropertyPath()] = $error->getMessage();
            }

            $result['error']['data'] = $errors;
        }

        $this->setData($result);
    }
}
