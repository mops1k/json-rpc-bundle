<?php

namespace JsonRpcBundle\Response;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class JsonRpcCollectionResponse extends JsonResponse
{
    public function __construct(
        /**
         * @var array<JsonRpcResponse|JsonRpcErrorResponse>
         */
        protected array $response,
        int $status = Response::HTTP_OK,
        array $headers = []
    ) {
        parent::__construct($this->prepareResponse(), $status, $headers, false);
    }

    public function prepareResponse(): array
    {
        $data = [];
        foreach ($this->response as $response) {
            $data[] = \json_decode($response->data, true);
        }

        return $data;
    }
}
