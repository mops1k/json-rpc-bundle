<?php

namespace JsonRpcBundle\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class JsonRpcResponse extends JsonResponse
{
    public function __construct(
        mixed $data = null,
        int|string|null $id = null,
        int $status = 200,
        array $headers = ['Content-Type' => 'application/json']
    ) {
        parent::__construct(null, $status, $headers);

        $data ??= new \ArrayObject();
        $this->setResult($data, $id);
    }

    public function setResult(mixed $data, int|string|null $id): void
    {
        $result = [
            'jsonrpc' => '2.0',
            'result' => $data,
            'id' => $id,
        ];

        $this->setData($result);
    }
}
